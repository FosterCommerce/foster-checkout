<?php

namespace fostercommerce\craftfostercheckout\web\assets\checkout;

use Craft;
use craft\web\AssetBundle;

/**
 * Checkout asset bundle
 */
class CheckoutAsset extends AssetBundle
{

    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->js = ['js/app.js'];
        parent::init();
    }
}
