<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class OptionConfig extends Model
{
	/**
	 * Whether to show the "save for later" button
	 */
	public bool $enableSaveForLater = false;

	/**
	 * Whether to show the shipping estimator
	 */
	public bool $enableEstimatedShipping = false;

	/**
	 * Whether to show the free shipping message
	 */
	public bool $enableFreeShippingMessage = false;

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
	 * Whether to show the line item options
	 *
	 * If false, no line item options will be displayed.
	 *
	 * If set to true or an empty string, then all line items will be shown.
	 *
	 * If set to a string, then line items prefixed with that value will be excluded from being shown.
	 */
	public bool|string $enableLineItemOptions = '_';

	/**
	 * The label to display for the estimated tax on the cart summary
	 */
	public string $estimatedTaxLabel = 'Estimated tax';

	/**
	 * The label to display for the tax on the cart summary
	 */
	public string $taxLabel = 'Tax';

	/**
	 * The Klaviyo list ID to subscribe the customer to
	 */
	public ?string $klaviyoListId = null;

	/**
	 * Delivery date configuration
	 */
	public DeliveryDateConfig $deliveryDate;

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

		parent::__construct($config);
	}
}
