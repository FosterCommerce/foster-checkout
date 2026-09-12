<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\base\FieldLayoutElement;
use craft\commerce\base\Gateway;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\enums\LineItemType;
use craft\commerce\helpers\Currency;
use craft\commerce\models\LineItem;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\Plugin as Commerce;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\User;
use craft\fieldlayoutelements\addresses\AddressField;
use craft\fieldlayoutelements\addresses\CountryCodeField;
use craft\fieldlayoutelements\addresses\LabelField;
use craft\fieldlayoutelements\addresses\OrganizationField;
use craft\fieldlayoutelements\addresses\OrganizationTaxIdField;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\FullNameField;
use craft\helpers\StringHelper;
use craft\web\Request as WebRequest;
use DateTime;
use fostercommerce\advanceddiscounts\Plugin as AdvancedDiscounts;
use fostercommerce\advanceddiscounts\variables\AdvancedDiscountsVariable;
use fostercommerce\fostercheckout\events\DefineCheckoutContactEvent;
use fostercommerce\fostercheckout\events\DefinePaymentBlockEvent;
use fostercommerce\fostercheckout\events\DefinePaymentFormParamsEvent;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\helpers\CheckoutAddressFormatter;
use fostercommerce\fostercheckout\models\DeliveryDate;
use fostercommerce\fostercheckout\models\LineItemOptionRule;
use fostercommerce\fostercheckout\models\PaymentGatewayConfig;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\models\ValueConfig;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Checkout service
 *
 * @phpstan-import-type RenderableField from CheckoutFieldLayouts
 * @phpstan-import-type RenderableUiElement from CheckoutFieldLayouts
 * @phpstan-type AddressFormElement array{type: string, required: bool, width: int, field: ?RenderableField}
 * @phpstan-type LinksTable array<array-key, array{text: non-empty-string, url: non-empty-string}>
 * @phpstan-type CheckoutShippingMethod array{handle: string, name: string, description: string, price: float, priceAsCurrency: string}
 * @phpstan-type CheckoutTotals array{
 *     itemsAsCurrency: string,
 *     shipping: float,
 *     shippingAsCurrency: string,
 *     taxAsCurrency: string,
 *     total: float,
 *     totalAsCurrency: string,
 *     currency: string,
 *     discounts: list<array{name: string, amountAsCurrency: string}>,
 *     vouchers: list<array{name: string, amountAsCurrency: string}>
 * }
 * @phpstan-type CheckoutLineItemTotals array{
 *     discountNames: list<string>,
 *     originalTotalAsCurrency: string,
 *     priceAsCurrency: string,
 *     hasDiscount: bool
 * }
 * @phpstan-type CheckoutLiveState array{
 *     shippingMethods: list<CheckoutShippingMethod>,
 *     shippingMethodHandle: string,
 *     totals: CheckoutTotals,
 *     lineItemTotals: array<int, CheckoutLineItemTotals>,
 *     addressLabels: array<int, string>,
 *     shippingPreview: string,
 *     customerPickup: bool,
 *     couponName: ?string,
 *     couponMessages: list<string>,
 *     couponCodeError?: string
 * }
 */
class Checkout extends Component
{
	/**
	 * @event DefineCheckoutContactEvent The event that is triggered when naming the contact shown at checkout.
	 *
	 * ```php
	 * use fostercommerce\fostercheckout\events\DefineCheckoutContactEvent;
	 * use fostercommerce\fostercheckout\services\Checkout;
	 * use yii\base\Event;
	 *
	 * Event::on(
	 *     Checkout::class,
	 *     Checkout::EVENT_DEFINE_CONTACT,
	 *     function (DefineCheckoutContactEvent $event) {
	 *         $event->value = $event->order->getCustomer()?->fullName;
	 *     }
	 * );
	 * ```
	 *
	 * @since 1.1.0
	 */
	public const string EVENT_DEFINE_CONTACT = 'defineContact';

	/**
	 * @event DefinePaymentBlockEvent The event that is triggered when deciding whether an order can be paid at checkout.
	 *
	 * ```php
	 * use fostercommerce\fostercheckout\events\DefinePaymentBlockEvent;
	 * use fostercommerce\fostercheckout\services\Checkout;
	 * use yii\base\Event;
	 *
	 * Event::on(
	 *     Checkout::class,
	 *     Checkout::EVENT_DEFINE_PAYMENT_BLOCK,
	 *     function (DefinePaymentBlockEvent $event) {
	 *         if ($event->order->getCustomer()?->isCredentialed === false) {
	 *             $event->reason = 'Activate your account before paying.';
	 *         }
	 *     }
	 * );
	 * ```
	 *
	 * @since 1.4.1
	 */
	public const string EVENT_DEFINE_PAYMENT_BLOCK = 'definePaymentBlock';

	/**
	 * @event DefinePaymentFormParamsEvent The event that is triggered before a gateway's payment form is rendered.
	 *
	 * ```php
	 * use fostercommerce\fostercheckout\events\DefinePaymentFormParamsEvent;
	 * use fostercommerce\fostercheckout\services\Checkout;
	 * use yii\base\Event;
	 *
	 * Event::on(
	 *     Checkout::class,
	 *     Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS,
	 *     function (DefinePaymentFormParamsEvent $event) {
	 *         if ($event->gateway instanceof \craft\commerce\stripe\gateways\PaymentIntents) {
	 *             $event->params['elementOptions']['wallets']['link'] = 'never';
	 *         }
	 *     }
	 * );
	 * ```
	 *
	 * @since 1.4.2
	 */
	public const string EVENT_DEFINE_PAYMENT_FORM_PARAMS = 'definePaymentFormParams';

	/**
	 * @var array<string, array<int, string>>|null
	 */
	private ?array $addressRequiredFields = null;

	/**
	 * @var array<string, array<int, string>>|null
	 */
	private ?array $addressUsedFields = null;

	/**
	 * @var array<string, bool>
	 */
	private array $addressAccess = [];

	/**
	 * The contact shown at checkout.
	 *
	 * @since 1.1.0
	 */
	public function contact(Order $order): string
	{
		if (! $this->hasEventHandlers(self::EVENT_DEFINE_CONTACT)) {
			return (string) $order->email;
		}

		$defineContactEvent = new DefineCheckoutContactEvent([
			'order' => $order,
		]);

		$this->trigger(self::EVENT_DEFINE_CONTACT, $defineContactEvent);

		return $defineContactEvent->value ?? (string) $order->email;
	}

	/**
	 * Why the order cannot be paid at checkout, or null when it can. Shown in place of the payment form.
	 *
	 * @since 1.4.1
	 */
	public function paymentBlock(Order $order): ?string
	{
		if (! $this->hasEventHandlers(self::EVENT_DEFINE_PAYMENT_BLOCK)) {
			return null;
		}

		$definePaymentBlockEvent = new DefinePaymentBlockEvent([
			'order' => $order,
		]);

		$this->trigger(self::EVENT_DEFINE_PAYMENT_BLOCK, $definePaymentBlockEvent);

		return $definePaymentBlockEvent->reason;
	}

	/**
	 * The parameters a gateway's payment form is rendered with, after any handler has changed them.
	 *
	 * @param array<string, mixed> $params
	 * @return array<string, mixed>
	 * @since 1.4.2
	 */
	public function paymentFormParams(Order $order, GatewayInterface $gateway, array $params): array
	{
		if (! $this->hasEventHandlers(self::EVENT_DEFINE_PAYMENT_FORM_PARAMS)) {
			return $params;
		}

		$definePaymentFormParamsEvent = new DefinePaymentFormParamsEvent([
			'order' => $order,
			'gateway' => $gateway,
			'params' => $params,
		]);

		$this->trigger(self::EVENT_DEFINE_PAYMENT_FORM_PARAMS, $definePaymentFormParamsEvent);

		return $definePaymentFormParamsEvent->params;
	}

	/**
	 * Whether the signed-in user may change the customer's saved addresses.
	 *
	 * @since 1.1.0
	 */
	public function canSaveAddresses(Order $order): bool
	{
		return $this->addressAccess($order, 'save');
	}

	/**
	 * Whether the signed-in user may ship to the customer's saved addresses.
	 *
	 * @since 1.1.0
	 */
	public function canViewAddresses(Order $order): bool
	{
		return $this->addressAccess($order, 'view');
	}

	/**
	 * Whether Klaviyo should receive events for this order.
	 *
	 * Someone buying on another account's behalf would file the events under a customer who is not
	 * the shopper, so the order goes untracked.
	 */
	public function klaviyoTrackingEnabled(Order $order): bool
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('klaviyo-connect-plus')) {
			return false;
		}

		$user = Craft::$app->getUser()->getIdentity();

		if (! $user instanceof User) {
			return true;
		}

		return $user->email === $order->email;
	}

	public function addressFormatter(): CheckoutAddressFormatter
	{
		return new CheckoutAddressFormatter();
	}

	/**
	 * Required address fields for every country the store sells to, keyed by country code.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function addressRequiredFields(): array
	{
		if ($this->addressRequiredFields !== null) {
			return $this->addressRequiredFields;
		}

		$addressFormatRepository = Craft::$app->getAddresses()->getAddressFormatRepository();
		$requiredFields = [];

		foreach (array_keys($this->storeCountries()) as $countryCode) {
			// AddressField is a class of string constants, so these are the property names themselves.
			/** @var array<int, string> $countryRequiredFields */
			$countryRequiredFields = $addressFormatRepository->get($countryCode)->getRequiredFields();
			$requiredFields[$countryCode] = $countryRequiredFields;
		}

		return $this->addressRequiredFields = $requiredFields;
	}

	/**
	 * Address fields each country's format actually uses, keyed by country code.
	 *
	 * Read through the Addresses service rather than the format repository, so `EVENT_DEFINE_USED_FIELDS`
	 * applies, and this plugin adds the administrative area for GB through it.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function addressUsedFields(): array
	{
		if ($this->addressUsedFields !== null) {
			return $this->addressUsedFields;
		}

		$addressesService = Craft::$app->getAddresses();
		$usedFields = [];

		foreach (array_keys($this->storeCountries()) as $countryCode) {
			$usedFields[$countryCode] = array_values($addressesService->getUsedFields($countryCode));
		}

		return $this->addressUsedFields = $usedFields;
	}

	/**
	 * @return array<string, string>
	 * @throws InvalidConfigException when the store has no countries selected
	 */
	public function storeCountries(): array
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$store = $commerce->getStores()->getCurrentStore();
		$countries = $store->getSettings()->getCountriesList();

		// Fail here rather than in the address form, which would render an empty country select
		if ($countries === []) {
			throw new InvalidConfigException(
				"The {$store->getName()} store has no countries selected, so checkout cannot build an address form. "
				. 'Choose them under Commerce, Store Management, General.'
			);
		}

		return $countries;
	}

	/**
	 * Copy the pickup location or a saved address the cart update named onto the order.
	 *
	 * Needed for an order filed under someone other than the signed-in user, since a posted address
	 * id is only resolved against the signed-in user's own book.
	 */
	public function applyCustomerAddresses(Order $order, WebRequest $request): void
	{
		$pickupChosen = $this->applyCustomerPickup($order, $request);
		$customerId = $order->getCustomer()?->id;

		// Leave both addresses alone when billing drives shipping, since that path resolves elsewhere
		if ($request->getBodyParam('shippingAddressSameAsBilling') || $customerId === null || ! $this->canViewAddresses($order)) {
			return;
		}

		if (! $pickupChosen) {
			$shippingAddress = $this->customerAddress($customerId, $request->getBodyParam('shippingAddressId'), $order->sourceShippingAddressId);

			if ($shippingAddress instanceof Address) {
				$order->sourceShippingAddressId = $shippingAddress->id;
				$this->bookAddressOntoOrder($order, $shippingAddress, 'shippingAddress');
			}
		}

		// A pickup order is billed to the customer, never to the store location
		if ($request->getBodyParam('billingAddressSameAsShipping') && ! $pickupChosen) {
			$this->mirrorShippingToBilling($order);

			return;
		}

		$billingAddress = $this->customerAddress($customerId, $request->getBodyParam('billingAddressId'), $order->sourceBillingAddressId);

		if ($billingAddress instanceof Address) {
			$order->sourceBillingAddressId = $billingAddress->id;
			$this->bookAddressOntoOrder($order, $billingAddress, 'billingAddress');
		}
	}

	/**
	 * Whether the checkout offers pickup at the order's store location. Off while the location is
	 * blank, since one always exists.
	 */
	public function customerPickupAvailable(Order $order): bool
	{
		if (! $this->settings()->enableCustomerPickup) {
			return false;
		}

		return trim((string) $this->pickupLocation($order)?->addressLine1) !== '';
	}

	public function customerPickupLabel(): string
	{
		$label = trim((string) $this->settings()->customerPickupLabel);

		return $label !== '' ? Craft::t('site', $label) : Craft::t('foster-checkout', 'address.customerPickup');
	}

	/**
	 * The store location, formatted the way a saved address is.
	 */
	public function customerPickupPreview(Order $order): string
	{
		return $this->checkoutAddressPreview($this->pickupLocation($order));
	}

	/**
	 * Whether the order ships to the store location, since no pickup flag is stored on the order.
	 */
	public function isCustomerPickup(Order $order): bool
	{
		if (! $this->customerPickupAvailable($order)) {
			return false;
		}

		$shippingAddress = $order->getShippingAddress();
		$location = $this->pickupLocation($order);

		if (! $shippingAddress instanceof Address || ! $location instanceof Address) {
			return false;
		}

		foreach (['countryCode', 'postalCode', 'addressLine1'] as $attribute) {
			if ($this->comparableAddressPart($shippingAddress->{$attribute}) !== $this->comparableAddressPart($location->{$attribute})) {
				return false;
			}
		}

		return true;
	}

	public function content(): Content
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->getContent();
	}

	public function settings(): Settings
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		/** @var Settings $settings */
		$settings = $plugin->getSettings();

		return $settings;
	}

	/**
	 * The site template places the tag itself, so the URL is published rather than registered.
	 */
	public function jsBundle(): string
	{
		/** @var string $bundleUrl */
		$bundleUrl = Craft::$app->assetManager->getPublishedUrl(
			'@fostercheckout/web/assets/checkout/dist/js',
			true,
			'alpine.js'
		);

		return $bundleUrl;
	}

	public function tailwindBundle(): string
	{
		/** @var string $bundleUrl */
		$bundleUrl = Craft::$app->assetManager->getPublishedUrl(
			'@fostercheckout/web/assets/checkout/vendor',
			true,
			'tailwind-3.4.17.js'
		);

		return $bundleUrl;
	}

	/**
	 * @return ?LinksTable
	 */
	public function links(string $field): ?array
	{
		$links = $this->content()->get("links.{$field}");

		if (! is_array($links)) {
			return null;
		}

		// A row missing either column would render a link with no destination or no label
		$complete = array_filter(
			$links,
			static fn ($link): bool => is_array($link) && ($link['text'] ?? '') !== '' && ($link['url'] ?? '') !== ''
		);

		/** @var LinksTable $completeLinks */
		$completeLinks = array_values($complete);

		return $completeLinks;
	}

	/**
	 * Stored copy is rendered as a Twig template, so a note can reference the cart or order.
	 *
	 * @param array<non-empty-string, mixed> $context additional context to pass to the twig template
	 */
	public function note(string $field, array $context = []): ?string
	{
		$note = $this->content()->get("notes.{$field}");

		if (! is_string($note)) {
			return null;
		}

		return Craft::$app->getView()->renderString($note, $context);
	}

	/**
	 * Gets the line items image field based on the products settings
	 *
	 * @return ?array{handle: string, level: string}
	 */
	public function lineItemImageField(string $productType): ?array
	{
		$products = $this->settings()->products;
		$productConfig = $products[$productType] ?? null;

		if ($productConfig?->variantImageHandle !== null) {
			return [
				'handle' => $productConfig->variantImageHandle,
				'level' => 'variant',
			];
		}

		if ($productConfig?->productImageHandle !== null) {
			return [
				'handle' => $productConfig->productImageHandle,
				'level' => 'product',
			];
		}

		return null;
	}

	/**
	 * Gets a line items image asset based on the config settings for the product type
	 *
	 * @throws InvalidConfigException
	 */
	public function lineItemImage(LineItem $lineItem): ?Asset
	{
		if ($lineItem->type === LineItemType::Custom) {
			return null;
		}

		/** @var ?Variant $variant */
		$variant = $lineItem->getPurchasable();
		if (! $variant instanceof Variant) {
			return null;
		}

		/** @var Product $product */
		$product = $variant->getOwner();

		/** @var string $productTypeHandle */
		$productTypeHandle = $product->type->handle;

		$fieldInfo = $this->lineItemImageField($productTypeHandle);

		if ($fieldInfo !== null) {
			/** @var AssetQuery<array-key, Asset> $query */
			$query = $fieldInfo['level'] === 'variant' ? $variant->{$fieldInfo['handle']} : $product->{$fieldInfo['handle']};

			/** @var ?Asset $image */
			$image = $query->one();

			return $image;
		}

		return null;
	}

	/**
	 * @return array<array-key, array{name: string, value: string}>
	 */
	public function getLineItemOptions(LineItem $lineItem): array
	{
		$lineItems = $this->settings()->lineItems;

		if (! $lineItems->enableLineItemOptions) {
			return [];
		}

		$hiddenPrefix = $lineItems->hiddenLineItemOptionPrefix;
		$maxLength = $lineItems->lineItemOptionValueMaxLength;
		$displayed = [];

		foreach ($lineItem->options as $optionName => $optionValue) {
			$optionName = (string) $optionName;

			if ($hiddenPrefix !== '' && str_starts_with($optionName, $hiddenPrefix)) {
				continue;
			}

			$rewritten = $this->rewriteOption(
				$optionName,
				is_scalar($optionValue) ? (string) $optionValue : '',
				$this->settings()->lineItemOptionRules
			);

			if ($maxLength !== null && $maxLength > 0) {
				$rewritten['value'] = StringHelper::safeTruncate($rewritten['value'], $maxLength, '…');
			}

			$displayed[] = $rewritten;
		}

		return $displayed;
	}

	public function getDeliveryDate(Order $order): false|DeliveryDate
	{
		$deliveryDateConfig = $this->settings()->options->deliveryDate;

		$context = [
			'order' => $order,
		];

		$display = $deliveryDateConfig->display->getValue($context);
		if (! is_bool($display)) {
			$display = filter_var($display, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		}

		if ($display === false) {
			return false;
		}

		$estimate = $deliveryDateConfig->estimate->getValue($context);
		if (is_string($estimate) || is_int($estimate)) {
			$intValue = filter_var($estimate, FILTER_VALIDATE_INT);
			if ($intValue !== false) {
				$estimate = $order->dateOrdered instanceof DateTime ? (clone $order->dateOrdered)->modify("+{$intValue} days") : null;
			}
		}

		return new DeliveryDate([
			'label' => $this->contentOrConfig('deliveryDateLabel', $deliveryDateConfig->label, $context),
			'message' => $this->contentOrConfig('deliveryDateMessage', $deliveryDateConfig->message, $context),
			'estimate' => $estimate,
		]);
	}

	public function getManualGatewayConfig(string $gateway): ?PaymentGatewayConfig
	{
		return $this->settings()->paymentGateways[$gateway] ?? null;
	}

	/**
	 * @return array<int, RenderableField|RenderableUiElement>
	 */
	public function gatewayFields(string $gatewayHandle, ?Order $order = null): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->getCheckoutFieldLayouts()->getRenderableFields($gatewayHandle, $order);
	}

	/**
	 * @return array<int, RenderableField|RenderableUiElement>
	 */
	public function checkoutFields(string $position, ?Order $order = null): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->getCheckoutFieldLayouts()->getRenderableCheckoutFields($position, $order);
	}

	/**
	 * Labels of a position's required fields the order has no value for.
	 *
	 * @return list<string>
	 */
	public function missingRequiredFields(string $position, ?Order $order = null): array
	{
		$missing = [];

		foreach ($this->checkoutFields($position, $order) as $field) {
			if (! array_key_exists('required', $field)) {
				continue;
			}

			if ($field['required'] && ($field['value'] === '' || $field['value'] === [])) {
				$missing[] = $field['label'];
			}
		}

		return $missing;
	}

	/**
	 * The address form, in the order and widths the address field layout sets.
	 *
	 * @return array<int, AddressFormElement>
	 */
	public function addressFields(?Address $address = null, bool $includeLabel = false): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$layout = Craft::$app->getAddresses()->getFieldLayout();

		// Without an address there is nothing to test a visibility condition against, so list them all
		$layoutElements = $address instanceof Address
			? $layout->getVisibleElementsByType(FieldLayoutElement::class, $address)
			: $layout->getAllElements();

		$elements = [];
		$settings = $this->settings();

		foreach ($this->addressLayoutFields($layoutElements) as $type => $layoutElement) {
			$configurable = $this->isConfigurableAddressField($type, $layoutElement);
			$attribute = $layoutElement->attribute();

			if ($configurable && in_array($attribute, $settings->hiddenAddressFields, true)) {
				continue;
			}

			// Skip the label on an order address, since its title is overwritten on save
			if ($type === 'label' && (! $includeLabel || ! $settings->showAddressLabelField)) {
				continue;
			}

			$field = $layoutElement instanceof CustomField
				? $plugin->getCheckoutFieldLayouts()->renderableField($layoutElement)
				: null;

			// A custom field whose type has no storefront input has nothing to show the customer
			if ($type === 'custom' && $field === null) {
				continue;
			}

			$elements[] = [
				'type' => $type,
				'required' => $layoutElement->required
					|| ($configurable && in_array($attribute, $settings->requiredAddressFields, true)),
				'width' => $layoutElement->width,
				'field' => $field,
			];
		}

		return $elements;
	}

	/**
	 * @param array<non-empty-string, mixed> $context additional context to pass to the twig template
	 */
	public function gatewayNote(string $gatewayHandle, array $context = []): ?string
	{
		return $this->contentOrConfig(
			"gateways.{$gatewayHandle}",
			$this->getManualGatewayConfig($gatewayHandle)?->note,
			$context
		);
	}

	public function gatewayLabel(?Gateway $gateway): string
	{
		if (! $gateway instanceof Gateway) {
			return '';
		}

		$label = $this->getManualGatewayConfig((string) $gateway->handle)?->label ?? '';

		return Craft::t('site', $label === '' ? (string) $gateway->name : $label);
	}

	public function subscribeText(): ?string
	{
		return $this->contentOrConfig('subscribe', $this->settings()->options->subscribe);
	}

	/**
	 * Copy for a shipping method panel with nothing to offer.
	 *
	 * @since 1.4.2
	 */
	public function noShippingMethodsText(Order $cart): string
	{
		return $this->contentOrConfig('noShippingMethods', null, [
			'cart' => $cart,
		]) ?? Craft::t('foster-checkout', 'shipping.noMethodAvailable') . '.';
	}

	/**
	 * @return CheckoutLiveState
	 */
	public function checkoutLiveState(Order $cart): array
	{
		$shippingMethods = $this->checkoutShippingMethods($cart);
		$handles = array_column($shippingMethods, 'handle');
		$cartHandle = $cart->shippingMethodHandle ?? '';
		$shippingMethodHandle = $cartHandle !== '' && in_array($cartHandle, $handles, true)
			? $cartHandle
			: ($handles[0] ?? '');

		return [
			'shippingMethods' => $shippingMethods,
			'shippingMethodHandle' => $shippingMethodHandle,
			'totals' => $this->checkoutTotals($cart),
			'lineItemTotals' => $this->checkoutLineItemTotals($cart),
			'addressLabels' => $this->checkoutAddressLabels($cart),
			'shippingPreview' => $this->checkoutAddressPreview($cart->getShippingAddress()),
			'customerPickup' => $this->isCustomerPickup($cart),
			'couponName' => $this->couponName($cart),
			'couponMessages' => $this->couponMessages($cart),
		];
	}

	/**
	 * Address fields an admin may hide or require at the checkout, as control panel options.
	 *
	 * @return array<int, array{label: string, value: string}>
	 */
	public function configurableAddressFields(): array
	{
		$options = [];
		$layoutElements = Craft::$app->getAddresses()->getFieldLayout()->getAllElements();

		foreach ($this->addressLayoutFields($layoutElements) as $type => $layoutElement) {
			if (! $this->isConfigurableAddressField($type, $layoutElement)) {
				continue;
			}

			$options[] = [
				'label' => $this->addressFieldLabel($type, $layoutElement),
				'value' => $layoutElement->attribute(),
			];
		}

		return $options;
	}

	/**
	 * The line items total, with any discount belonging to a single item already taken off.
	 */
	public function itemsTotal(Order $order): string
	{
		$teller = $order->getTeller();
		$lineItemDiscount = '0';

		foreach ($order->getAdjustments() ?? [] as $adjustment) {
			if ($adjustment->type === 'discount' && $adjustment->lineItemId) {
				$lineItemDiscount = $teller->add($lineItemDiscount, $adjustment->amount);
			}
		}

		return Currency::formatAsCurrency(
			$teller->add($order->getItemSubtotal(), $lineItemDiscount),
			$order->currency
		);
	}

	/**
	 * What a line item would have cost at its original price, before any sale.
	 */
	public function lineItemOriginalTotal(LineItem $lineItem): string
	{
		/** @var Order $order a line item in a cart always belongs to one */
		$order = $lineItem->getOrder();

		return Currency::formatAsCurrency(
			$order->getTeller()->multiply($lineItem->price, (string) $lineItem->qty),
			$order->currency
		);
	}

	/**
	 * Gift Voucher snapshots the code element, whose description is translated and so cannot be parsed.
	 */
	public function voucherCode(OrderAdjustment $adjustment): ?string
	{
		$codeKey = $adjustment->sourceSnapshot['codeKey'] ?? null;

		return is_string($codeKey) && $codeKey !== '' ? $codeKey : null;
	}

	public function voucherLabel(OrderAdjustment $adjustment): string
	{
		return $this->voucherCode($adjustment) ?? Craft::t(FosterCheckout::HANDLE, 'voucher.fallbackLabel');
	}

	/**
	 * Name of the discount that owns the cart's coupon code.
	 *
	 * @since 1.2.0
	 */
	public function couponName(Order $cart): ?string
	{
		$couponCode = $cart->couponCode;
		if ($couponCode === null || $couponCode === '') {
			return null;
		}

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$discount = $commerce->getDiscounts()->getDiscountByCode($couponCode, $cart->storeId);
		if ($discount !== null) {
			return $discount->name;
		}

		if (! Craft::$app->getPlugins()->isPluginEnabled('advanced-discounts')) {
			return null;
		}

		/** @var AdvancedDiscounts $advancedDiscounts */
		$advancedDiscounts = AdvancedDiscounts::getInstance();

		return $advancedDiscounts->getDiscounts()->getDiscountByCode($couponCode)?->name;
	}

	/**
	 * Advanced Discounts' messages for the cart, such as why its coupon did not apply.
	 *
	 * @return list<string>
	 * @since 1.2.0
	 */
	public function couponMessages(Order $cart): array
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('advanced-discounts')) {
			return [];
		}

		return (new AdvancedDiscountsVariable())->getMessages($cart);
	}

	/**
	 * A saved address of the customer, when the order's source id does not already match.
	 */
	private function customerAddress(int $customerId, mixed $postedId, ?int $sourceId): ?Address
	{
		if (! is_numeric($postedId) || (int) $postedId === $sourceId) {
			return null;
		}

		/** @var ?Address $address */
		$address = Address::find()
			->id((int) $postedId)
			->ownerId($customerId)
			->one();

		return $address;
	}

	/**
	 * Copy the order's shipping address over its billing address.
	 *
	 * Copy rather than share, since an address the order names twice is duplicated on every save.
	 */
	private function mirrorShippingToBilling(Order $order): void
	{
		if ($order->sourceBillingAddressId === $order->sourceShippingAddressId && $order->hasMatchingAddresses()) {
			return;
		}

		$shippingAddress = $order->getShippingAddress();

		$order->sourceBillingAddressId = $order->sourceShippingAddressId;
		$order->setBillingAddress(
			$shippingAddress instanceof Address ? $this->duplicateOntoOrder($shippingAddress, $order) : null
		);
	}

	/**
	 * Craft resolves the owner with an element query, so each answer is kept for the request.
	 */
	private function addressAccess(Order $order, string $check): bool
	{
		$customer = $order->getCustomer();
		$user = Craft::$app->getUser()->getIdentity();

		if (! $customer instanceof User || ! $user instanceof User) {
			return false;
		}

		$cacheKey = "{$customer->id}:{$user->id}:{$check}";

		if (isset($this->addressAccess[$cacheKey])) {
			return $this->addressAccess[$cacheKey];
		}

		$address = new Address([
			'ownerId' => $customer->id,
		]);

		return $this->addressAccess[$cacheKey] = $check === 'save'
			? Craft::$app->getElements()->canSave($address, $user)
			: Craft::$app->getElements()->canView($address, $user);
	}

	/**
	 * Set the store location as the shipping address when pickup is chosen, and unset it when not.
	 */
	private function applyCustomerPickup(Order $order, WebRequest $request): bool
	{
		$postedPickup = $request->getBodyParam('shippingPickup');

		if ($postedPickup === null || ! $this->customerPickupAvailable($order)) {
			return false;
		}

		if (! $postedPickup) {
			// Only unset a location no posted address replaced, since those are applied before validation.
			// Keep the source id, so the address step reselects the invalid saved address.
			if ($this->isCustomerPickup($order) && ! $request->getBodyParam('shippingAddress')) {
				$order->setShippingAddress(null);
			}

			return false;
		}

		if ($this->isCustomerPickup($order)) {
			return true;
		}

		/** @var Address $location */
		$location = $this->pickupLocation($order);
		$collectorName = trim((string) $order->getCustomer()?->fullName);

		if ($collectorName === '') {
			$collectorName = trim((string) $order->getBillingAddress()?->fullName);
		}

		if ($collectorName === '') {
			$collectorName = $this->customerPickupLabel();
		}

		/** @var Address $pickupAddress */
		$pickupAddress = Craft::$app->getElements()->duplicateElement($location, [
			'primaryOwner' => $order,
			'owner' => $order,
			// The address names who collects, since the location cannot. The name parts are cleared so
			// the full name is split on save rather than kept beside the location's own.
			'fullName' => $collectorName,
			'firstName' => null,
			'lastName' => null,
		]);

		$order->sourceShippingAddressId = null;
		$order->setShippingAddress($pickupAddress);

		return true;
	}

	private function pickupLocation(Order $order): ?Address
	{
		return $order->getStore()->getSettings()->getLocationAddress();
	}

	private function comparableAddressPart(?string $addressPart): string
	{
		return StringHelper::toLowerCase(trim((string) $addressPart));
	}

	/**
	 * Copy a saved address onto the order, or report its errors, since duplicating an invalid element throws.
	 */
	private function bookAddressOntoOrder(Order $order, Address $address, string $attribute): void
	{
		if (! $address->validate()) {
			$order->addModelErrors($address, $attribute);

			return;
		}

		$duplicate = $this->duplicateOntoOrder($address, $order);

		if ($attribute === 'shippingAddress') {
			$order->setShippingAddress($duplicate);

			return;
		}

		$order->setBillingAddress($duplicate);
	}

	private function duplicateOntoOrder(Address $address, Order $order): Address
	{
		/** @var Address $duplicate */
		$duplicate = Craft::$app->getElements()->duplicateElement($address, [
			'primaryOwner' => $order,
			'owner' => $order,
		]);

		return $duplicate;
	}

	/**
	 * Every rule tests the stored name and value, so renaming in one rule cannot hide the option
	 * from a later one. Rules run top to bottom, so the last to set a field wins.
	 *
	 * @param list<LineItemOptionRule> $rules
	 * @return array{name: string, value: string}
	 */
	private function rewriteOption(string $optionName, string $optionValue, array $rules): array
	{
		$displayed = [
			'name' => $optionName,
			'value' => $optionValue,
		];

		foreach ($rules as $rule) {
			if (! $rule->getCondition()->matches($optionName, $optionValue)) {
				continue;
			}

			if ((string) $rule->setName !== '') {
				$displayed['name'] = (string) $rule->setName;
			}

			if ((string) $rule->setValue !== '') {
				$displayed['value'] = (string) $rule->setValue;
			}
		}

		return $displayed;
	}

	/**
	 * A native field is labelled by the plugin rather than by Craft, so the option an admin picks
	 * reads the same as the field the customer sees.
	 */
	private function addressFieldLabel(string $type, BaseField $layoutElement): string
	{
		$label = match ($type) {
			'country' => 'addressFields.countryLabel',
			'fullName' => 'addressFields.fullnameLabel',
			'organization' => 'addressFields.organizationLabel',
			'organizationTaxId' => 'addressFields.organizationTaxIdLabel',
			default => null,
		};

		return $label === null
			? (string) $layoutElement->label()
			: Craft::t(FosterCheckout::HANDLE, $label);
	}

	/**
	 * The layout's elements the storefront can render, keyed by the type it renders them as.
	 *
	 * @param array<int, FieldLayoutElement> $layoutElements
	 * @return \Generator<string, BaseField>
	 */
	private function addressLayoutFields(array $layoutElements): \Generator
	{
		foreach ($layoutElements as $layoutElement) {
			$type = $this->addressElementType($layoutElement);

			if ($type === null) {
				continue;
			}

			if (! $layoutElement instanceof BaseField) {
				continue;
			}

			yield $type => $layoutElement;
		}
	}

	/**
	 * Country and the address block are what an address needs to resolve at all, and a field the
	 * layout already marks required cannot be loosened without failing Craft's validation.
	 */
	private function isConfigurableAddressField(string $type, BaseField $layoutElement): bool
	{
		return ! in_array($type, ['address', 'country', 'label'], true) && ! $layoutElement->required;
	}

	/**
	 * Latitude and longitude are not something a customer types, so they are not rendered.
	 */
	private function addressElementType(FieldLayoutElement $layoutElement): ?string
	{
		return match (true) {
			$layoutElement instanceof LabelField => 'label',
			$layoutElement instanceof CountryCodeField => 'country',
			$layoutElement instanceof FullNameField => 'fullName',
			$layoutElement instanceof OrganizationTaxIdField => 'organizationTaxId',
			$layoutElement instanceof OrganizationField => 'organization',
			$layoutElement instanceof AddressField => 'address',
			$layoutElement instanceof CustomField => 'custom',
			default => null,
		};
	}

	/**
	 * The one-line preview of each of the customer's saved addresses, keyed by id.
	 *
	 * @return array<int, string>
	 */
	private function checkoutAddressLabels(Order $cart): array
	{
		if (! $this->canViewAddresses($cart)) {
			return [];
		}

		$labels = [];

		/** @var Address $address */
		foreach ($cart->getCustomer()?->getAddresses() ?? [] as $address) {
			$labels[(int) $address->id] = $this->checkoutAddressPreview($address);
		}

		return $labels;
	}

	private function checkoutAddressPreview(?Address $address): string
	{
		if (! $address instanceof Address) {
			return '';
		}

		$formatted = $this->addressFormatter()->format($address);
		$name = trim((string) $address->fullName);

		if ($name === '') {
			return $formatted;
		}

		if ($formatted === '') {
			return $name;
		}

		return $name . ', ' . $formatted;
	}

	/**
	 * @return list<CheckoutShippingMethod>
	 */
	private function checkoutShippingMethods(Order $cart): array
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$methods = [];

		foreach ($cart->availableShippingMethodOptions as $handle => $method) {
			$rule = $commerce->getShippingMethods()->getMatchingShippingRule($cart, $method);
			$description = $rule?->getDescription() ?: '';

			$methods[] = [
				'handle' => (string) $handle,
				'name' => Craft::t('site', $method->name ?? (string) $handle),
				'description' => $description !== '' ? Craft::t('site', $description) : '',
				'price' => (float) $method->price,
				'priceAsCurrency' => $method->priceAsCurrency,
			];
		}

		return $methods;
	}

	/**
	 * What a coupon changes about each line item, keyed by line item id.
	 *
	 * @return array<int, CheckoutLineItemTotals>
	 */
	private function checkoutLineItemTotals(Order $cart): array
	{
		// Group the discounts once, since each line item would otherwise read the order's whole list
		$discountNames = [];

		foreach ($cart->getAdjustments() ?? [] as $adjustment) {
			if ($adjustment->type === 'discount' && $adjustment->lineItemId) {
				$discountNames[$adjustment->lineItemId][] = $adjustment->name;
			}
		}

		$lineItemTotals = [];

		foreach ($cart->getLineItems() as $lineItem) {
			$hasDiscount = $lineItem->getDiscount() !== 0.0;

			$lineItemTotals[(int) $lineItem->id] = [
				'discountNames' => $discountNames[$lineItem->id] ?? [],
				'originalTotalAsCurrency' => $this->lineItemOriginalTotal($lineItem),
				'priceAsCurrency' => $hasDiscount ? $lineItem->totalAsCurrency : $lineItem->subtotalAsCurrency,
				'hasDiscount' => $hasDiscount,
			];
		}

		return $lineItemTotals;
	}

	/**
	 * @return CheckoutTotals
	 */
	private function checkoutTotals(Order $cart): array
	{
		$discounts = [];
		$vouchers = [];

		foreach ($cart->getAdjustments() ?? [] as $adjustment) {
			if ($adjustment->type === 'discount') {
				if ($adjustment->lineItemId) {
					continue;
				}

				$discounts[] = [
					'name' => $adjustment->name,
					'amountAsCurrency' => $adjustment->amountAsCurrency,
				];
				continue;
			}

			if ($adjustment->type === 'voucher') {
				$vouchers[] = [
					'name' => $this->voucherLabel($adjustment),
					'amountAsCurrency' => $adjustment->amountAsCurrency,
				];
			}
		}

		return [
			'itemsAsCurrency' => $this->itemsTotal($cart),
			'shipping' => $cart->getTotalShippingCost(),
			'shippingAsCurrency' => $cart->totalShippingCostAsCurrency,
			'taxAsCurrency' => $cart->totalTaxAsCurrency,
			'total' => $cart->getTotal(),
			'totalAsCurrency' => $cart->totalAsCurrency,
			'currency' => (string) $cart->currency,
			'discounts' => $discounts,
			'vouchers' => $vouchers,
		];
	}

	/**
	 * Reads copy from content storage, falling back to its config value.
	 *
	 * The fallback covers installs that have not run the migration, and values config can express
	 * but content cannot, such as a gateway note defined as a PHP closure.
	 *
	 * @param array<non-empty-string, mixed> $context additional context to pass to the twig template
	 */
	private function contentOrConfig(string $field, ?ValueConfig $configValue, array $context = []): ?string
	{
		$note = $this->note($field, $context);

		if ($note !== null && trim($note) !== '') {
			return $note;
		}

		return $configValue instanceof ValueConfig ? $configValue->toStringWithContext($context) : null;
	}
}
