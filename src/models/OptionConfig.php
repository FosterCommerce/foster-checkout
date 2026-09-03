<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Model;

class OptionConfig extends Model
{
	/**
	 * Settings a config file may still name. Free shipping messaging moved to the advanced discounts plugin.
	 *
	 * @var list<string>
	 */
	private const array REMOVED_SETTINGS = ['enableFreeShippingMessage'];

	/**
	 * Whether to serve the single-page checkout. Existing sites stay on the stepped flow until this is turned on.
	 */
	public bool $enableSinglePageCheckout = false;

	/**
	 * Whether to show the "save for later" button
	 */
	public bool $enableSaveForLater = false;

	/**
	 * Unfinished: `estimated-shipping.twig` has its own condition commented out, so this only gates the include.
	 */
	public bool $enableEstimatedShipping = false;

	/**
	 * Whether to show the "No Image" placeholder images
	 */
	public bool $enablePlaceholderImages = false;

	/**
	 * Whether to enable CSS page transitions
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API#browser_compatibility for browser compatibility
	 */
	public bool $enablePageTransitions = false;

	/**
	 * Whether to show the "Made a mistake" function on the order completed page
	 *
	 * If disabled then the heading and text will not be displayed
	 */
	public bool $enableMadeAMistake = false;

	/**
	 * Whether to show line item options at all. Gates the prefix and the rewrite rules below.
	 */
	public bool $enableLineItemOptions = true;

	/**
	 * Options whose name starts with this are not shown. Empty shows every option.
	 */
	public string $hiddenLineItemOptionPrefix = '_';

	/**
	 * The Klaviyo list ID to subscribe the customer to
	 */
	public ?string $klaviyoListId = null;

	/**
	 * The text to display for the subscribe checkbox. Can also be a plain string, or a callable which returns a string
	 */
	public ValueConfig $subscribe;

	/**
	 * Delivery date configuration
	 */
	public DeliveryDateConfig $deliveryDate;

	/**
	 * An optional field handle for a field on Orders which will contain the payment due date
	 */
	public ?string $paymentDueDateFieldHandle = null;

	/**
	 * @var ?array<non-empty-string, mixed>
	 */
	public ?array $imagerXConfig = null;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['deliveryDate'])) {
			/** @var array<array-key, mixed> $deliveryDateConfig */
			$deliveryDateConfig = $config['deliveryDate'];
			$deliveryDate = new DeliveryDateConfig($deliveryDateConfig);
		} else {
			$deliveryDate = new DeliveryDateConfig();
		}


		$config['deliveryDate'] = $deliveryDate;

		$config['subscribe'] = ValueConfig::fromConfig('subscribe', $config);

		if (! isset($config['imagerXConfig'])) {
			$config['imagerXConfig'] = [];
		}

		$config = $this->upgradeLineItemOptions($config);

		parent::__construct($config);
	}

	#[\Override]
	public function __set($name, $value): void
	{
		if (in_array($name, self::REMOVED_SETTINGS, true)) {
			// The deprecator throws whenever a site sets throwExceptions, which craft-config ties to devMode
			Craft::warning("`{$name}` has been removed.", 'deprecation-error');

			return;
		}

		parent::__set($name, $value);
	}

	public function isSinglePageCheckout(): bool
	{
		return $this->enableSinglePageCheckout;
	}

	/**
	 * `enableLineItemOptions` was a toggle and a hidden-name prefix in one value.
	 *
	 * @param array<array-key, mixed> $config
	 * @return array<array-key, mixed>
	 */
	private function upgradeLineItemOptions(array $config): array
	{
		$posted = $config['enableLineItemOptions'] ?? null;

		if (! is_string($posted)) {
			return $config;
		}

		$config['enableLineItemOptions'] = true;

		if ($posted !== '' && ! isset($config['hiddenLineItemOptionPrefix'])) {
			$config['hiddenLineItemOptionPrefix'] = $posted;
		}

		return $config;
	}
}
