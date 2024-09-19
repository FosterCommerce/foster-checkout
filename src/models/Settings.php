<?php

namespace fostercommerce\craftfostercheckout\models;

use craft\base\Model;

/**
 * Foster Checkout settings
 */
class Settings extends Model
{
	public array $options = [
		'enableSaveForLater' => false,
		'enableEstimatedShipping' => false,
		'enableFreeShippingMessage' => false,
		'enablePlaceholderImages' => false,
		'enablePageTransitions' => false,
	];

	public array $branding = [
		'color' => '#1F2937',
		'font' => 'Rubik',
		'style' => 'rounded',
		'logo' => '',
		'title' => '',
	];

	public array $paths = [
		'cart' => '/cart',
		'checkout' => '/checkout',
		'cancel' => '/',
	];

	public array $products = [];

	public array $notes = [
		'cart' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'emptyCart' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'login' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'email' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'address' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'shipping' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'payment' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'order' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
	];
}
