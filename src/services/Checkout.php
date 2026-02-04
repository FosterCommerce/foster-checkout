<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\errors\InvalidFieldException;
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
 * @phpstan-type LinksTable array<array-key, array{text: non-empty-string, url: non-empty-string}>
 *
 * @property-read array $gateways
 * @property-read array $regions
 * @property-read string $paymentForm
 * @property-read array $discounts
 * @property-read array $countries
 */
class Checkout extends Component
{
	public function settings(): Settings
	{
		/** @var Settings $settings */
		$settings = FosterCheckout::getInstance()?->getSettings();

		return $settings;
	}

	/*
	 * Gets the 'dist' javascript asset bundle from the plugin
	 * Note: We are getting it this way as running view.registerAssetBundle() in the template does not output the
	 * script tag with type="module" attribute
	*/
	public function jsBundle(): string
	{
		/** @var string $bundleUrl */
		$bundleUrl = Craft::$app->assetManager->getPublishedUrl('@fostercheckout/web/assets/checkout/dist/js/alpine.js', true);

		return $bundleUrl;
	}

	/**
	 * @return ?LinksTable
	 */
	public function links(string $field): ?array
	{
		$links = $this->settings()->links;

		/** @var ?ValueConfig $links */
		$link = $links->{$field} ?? null;

		try {
			if ($link instanceof ValueConfig) {
				/** @var LinksTable $value */
				$value = $link->getValue();
				return $value;
			}
		} catch (InvalidFieldException) {
			return null;
		}

		return null;
	}

	/*
	 * Gets the custom note data based on the template page we are on
	*/
	public function note(string $field): string|null
	{
		$notes = $this->settings()->notes;

		/** @var ?ValueConfig $note */
		$note = $notes->{$field} ?? null;

		try {
			if ($note instanceof ValueConfig) {
				/** @throws InvalidFieldException */
				return (string) $note;
			}
		} catch (InvalidFieldException) {
			return null;
		}

		return null;
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
		$sku = $lineItem->getSku();
		$variant = Variant::find()->sku($sku)->one();
		if (! $variant instanceof Variant) {
			return null;
		}

		/** @var Product $product */
		$product = $variant->getOwner();

		/** @var string $productTypeHandle */
		$productTypeHandle = $product->type->handle;

		$fieldInfo = $this->lineItemImageField($productTypeHandle);

		if ($fieldInfo !== null) {
			$query = $fieldInfo['level'] === 'variant' ? $variant->{$fieldInfo['handle']} : $product->{$fieldInfo['handle']};

			/** @var AssetQuery $query */
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
				$estimate = $order->dateOrdered?->modify("+{$intValue} days");
			}
		}

		return new DeliveryDate([
			'label' => $deliveryDateConfig->label->getValue($context),
			'message' => $deliveryDateConfig->message->getValue($context),
			'estimate' => $estimate,
		]);
	}

	public function getManualGatewayConfig(string $gateway): ?PaymentGatewayConfig
	{
		return $this->settings()->paymentGateways[$gateway] ?? null;
	}
}
