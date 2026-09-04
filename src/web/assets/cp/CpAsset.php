<?php

namespace fostercommerce\fostercheckout\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;

class CpAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/dist';

	public $depends = [
		CraftCpAsset::class,
	];

	public $css = ['foster-checkout-cp.css'];
}
