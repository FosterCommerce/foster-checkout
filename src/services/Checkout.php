<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\DeliveryDate;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\models\TextConfig;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Checkout service
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
		$bundleUrl = Craft::$app->assetManager->getPublishedUrl('@fostercheckout/web/assets/checkout/dist/js/app.js', true);
		return $bundleUrl;
	}

	/*
	 * Gets the custom note data based on the template page we are on
	*/
	public function note(string $field): string|null
	{
		$notes = $this->settings()->notes;

		/** @var ?TextConfig $note */
		$note = $notes->{$field} ?? null;

		if ($note instanceof TextConfig) {
			return (string) $note;
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

		if (is_string($deliveryDateConfig->display)) {
			$display = Craft::$app->getView()->renderString($deliveryDateConfig->display, [
				'order' => $order,
			]);
			/** @var bool $display */
			$display = filter_var($display, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		} elseif (is_callable($deliveryDateConfig->display)) {
			$callable = $deliveryDateConfig->display;
			$display = $callable($order);
		} else {
			$display = $deliveryDateConfig->display;
		}

		if ($display === false) {
			return false;
		}

		if (is_string($deliveryDateConfig->value)) {
			$value = Craft::$app->getView()->renderString($deliveryDateConfig->value, [
				'order' => $order,
			]);
		} elseif (is_callable($deliveryDateConfig->value)) {
			$callable = $deliveryDateConfig->value;
			$value = $callable($order);
		} else {
			$value = null;
		}

		return new DeliveryDate([
			'label' => $deliveryDateConfig->label,
			'message' => $deliveryDateConfig->message,
			'value' => $value,
		]);
	}
}
