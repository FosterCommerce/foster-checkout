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
}
