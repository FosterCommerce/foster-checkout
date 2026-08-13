<?php

namespace fostercommerce\fostercheckout\web\assets\checkout;

use craft\web\AssetBundle;

/**
 * Checkout asset bundle
 */
class CheckoutAsset extends AssetBundle
{
	#[\Override]
	public function init(): void
	{
		$this->sourcePath = __DIR__ . '/dist';
		$this->js = ['js/alpine.js'];
		parent::init();
	}
}
