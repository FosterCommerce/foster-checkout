<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\base\ElementInterface;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\errors\InvalidFieldException;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\NoteConfig;
use fostercommerce\fostercheckout\models\Settings;
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
	public function note(string $field): object|string|null
	{
		$notes = $this->settings()->notes;

		/** @var ?NoteConfig $note */
		$note = $notes->{$field} ?? null;

		$elementHandle = $note?->elementHandle;
		$fieldHandle = $note?->fieldHandle;

		if ($elementHandle && $fieldHandle) {
			$global = GlobalSet::find()->handle($elementHandle)->one();
			$element = $global ?? Entry::find()->section($elementHandle)->one();

			// Get the content field data and parse it if necessary (for rich text fields like Redactor)
			/** @var ?ElementInterface $element */
			if ($element !== null) {
				try {
					/** @var object|string $content */
					$content = $element->getFieldValue($fieldHandle);

					return $content;
				} catch (InvalidFieldException) {
					// Do nothing
				}
			}
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
}
