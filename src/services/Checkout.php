<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\base\FieldLayoutElement;
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
use craft\fieldlayoutelements\addresses\AddressField;
use craft\fieldlayoutelements\addresses\CountryCodeField;
use craft\fieldlayoutelements\addresses\OrganizationField;
use craft\fieldlayoutelements\addresses\OrganizationTaxIdField;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\FullNameField;
use DateTime;
use fostercommerce\fostercheckout\formatters\CheckoutAddressFormatter;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\DeliveryDate;
use fostercommerce\fostercheckout\models\PaymentGatewayConfig;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\models\ValueConfig;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Checkout service
 *
 * @phpstan-import-type RenderableField from GatewayFieldLayouts
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
 * @phpstan-type CheckoutLiveState array{
 *     shippingMethods: list<CheckoutShippingMethod>,
 *     shippingMethodHandle: string,
 *     totals: CheckoutTotals,
 *     shippingPreview: string,
 *     couponCodeError?: string
 * }
 */
class Checkout extends Component
{
	/**
	 * @var array<string, array<int, string>>|null
	 */
	private ?array $addressRequiredFields = null;

	/**
	 * @var array<string, array<int, string>>|null
	 */
	private ?array $addressUsedFields = null;

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
	 * applies — this plugin adds the administrative area for GB through it.
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
	 */
	public function storeCountries(): array
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();

		return $commerce->getStores()->getCurrentStore()->getSettings()->getCountriesList();
	}

	public function content(): Content
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->content;
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
	 * Gets the 'dist' javascript asset bundle from the plugin
	 * Note: We are getting it this way as running view.registerAssetBundle() in the template does not output the
	 * script tag with type="module" attribute
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

	/**
	 * @return ?LinksTable
	 */
	public function links(string $field): ?array
	{
		$links = $this->content()->get("links.{$field}");

		if (! is_array($links)) {
			return null;
		}

		// A half-filled row would otherwise reach Twig as a missing attribute and fatal the page.
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
		$enableLineItemOptions = $this->settings()->options->enableLineItemOptions;
		if ($enableLineItemOptions === '') {
			$enableLineItemOptions = true;
		}

		if ($enableLineItemOptions === false) {
			return [];
		}

		/** @var array<array-key, array{name: string, value: string}> $options */
		$options = collect($lineItem->options)
			->filter(fn ($value, $name): bool =>
				// If the line item options are not set, or the name does not start with the line item options, return the option
				$enableLineItemOptions === true || ! str_starts_with((string) $name, $enableLineItemOptions))
			->map(fn ($value, $name): array => [
				'name' => $name,
				'value' => $value,
			])
			->toArray();

		return $options;
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
	 * @return array<int, RenderableField>
	 */
	public function gatewayFields(string $gatewayHandle, ?Order $order = null): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->gatewayFieldLayouts->getRenderableFields($gatewayHandle, $order);
	}

	/**
	 * The address form, in the order and widths the address field layout sets.
	 *
	 * @return array<int, AddressFormElement>
	 */
	public function addressFields(?Address $address = null): array
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

		foreach ($layoutElements as $layoutElement) {
			$type = $this->addressElementType($layoutElement);

			if ($type === null) {
				continue;
			}

			if (! $layoutElement instanceof BaseField) {
				continue;
			}

			$configurable = $this->isConfigurableAddressField($type, $layoutElement);

			if ($configurable && in_array($layoutElement->attribute(), $settings->hiddenAddressFields, true)) {
				continue;
			}

			$field = $layoutElement instanceof CustomField
				? $plugin->gatewayFieldLayouts->renderableField($layoutElement)
				: null;

			// A custom field whose type has no storefront input is dropped, same as elsewhere
			if ($type === 'custom' && $field === null) {
				continue;
			}

			$elements[] = [
				'type' => $type,
				'required' => $layoutElement->required
					|| ($configurable && in_array($layoutElement->attribute(), $settings->requiredAddressFields, true)),
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

	public function subscribeText(): ?string
	{
		return $this->contentOrConfig('subscribe', $this->settings()->options->subscribe);
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
			'shippingPreview' => $this->checkoutAddressPreview($cart->getShippingAddress()),
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

		foreach ($layoutElements as $layoutElement) {
			if (! $layoutElement instanceof BaseField) {
				continue;
			}

			$type = $this->addressElementType($layoutElement);

			if ($type === null) {
				continue;
			}

			if (! $this->isConfigurableAddressField($type, $layoutElement)) {
				continue;
			}

			$options[] = [
				'label' => (string) $layoutElement->label(),
				'value' => $layoutElement->attribute(),
			];
		}

		return $options;
	}

	/**
	 * Country and the address block are what an address needs to resolve at all, and a field the
	 * layout already marks required cannot be loosened without failing Craft's validation.
	 */
	private function isConfigurableAddressField(string $type, BaseField $layoutElement): bool
	{
		return ! in_array($type, ['address', 'country'], true) && ! $layoutElement->required;
	}

	/**
	 * Commerce overwrites an order address's `title`, and lat/long is not something a customer types,
	 * so neither reaches the storefront.
	 */
	private function addressElementType(FieldLayoutElement $layoutElement): ?string
	{
		return match (true) {
			$layoutElement instanceof CountryCodeField => 'country',
			$layoutElement instanceof FullNameField => 'fullName',
			$layoutElement instanceof OrganizationTaxIdField => 'organizationTaxId',
			$layoutElement instanceof OrganizationField => 'organization',
			$layoutElement instanceof AddressField => 'address',
			$layoutElement instanceof CustomField => 'custom',
			default => null,
		};
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
		$commerce = Commerce::getInstance();
		if ($commerce === null) {
			return [];
		}

		$methods = [];

		foreach ($cart->availableShippingMethodOptions as $handle => $method) {
			$rule = $commerce->getShippingMethods()->getMatchingShippingRule($cart, $method);
			$description = $rule?->getDescription() ?: '';

			$methods[] = [
				'handle' => (string) $handle,
				'name' => Craft::t('foster-checkout', $method->name ?? (string) $handle),
				'description' => $description !== '' ? Craft::t('foster-checkout', $description) : '',
				'price' => (float) $method->price,
				'priceAsCurrency' => $method->priceAsCurrency,
			];
		}

		return $methods;
	}

	/**
	 * @return CheckoutTotals
	 */
	private function checkoutTotals(Order $cart): array
	{
		$lineItemDiscount = 0.0;
		$discounts = [];
		$vouchers = [];

		foreach ($cart->getAdjustments() ?? [] as $adjustment) {
			if ($adjustment->type === 'discount') {
				if ($adjustment->lineItemId) {
					$lineItemDiscount += $adjustment->amount;
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

		$itemsAmount = (float) $cart->getTeller()->add($cart->getItemSubtotal(), $lineItemDiscount);

		return [
			'itemsAsCurrency' => Currency::formatAsCurrency($itemsAmount, $cart->currency),
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

	private function voucherLabel(OrderAdjustment $adjustment): string
	{
		$parts = explode('code ', (string) $adjustment->description, 2);
		if (isset($parts[1])) {
			return trim($parts[1], "'\" ");
		}

		return 'Voucher/Gift Card';
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
