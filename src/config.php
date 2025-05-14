<?php

/**
 * Foster Checkout config.php
 *
 * This file exists only as a template for the Foster Checkout settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'foster-checkout.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
	// Plugin options
	'options' => [
		// Whether or not to show the "save for later" button
		'enableSaveForLater' => false, // true|false

		// Whether or not to show the shipping estimator
		'enableEstimatedShipping' => false, // true|false

		// Whether or not to show the free shipping message
		'enableFreeShippingMessage' => false, // true|false

		// Whether or not to show the "No Image" placeholder images
		'enablePlaceholderImages' => false,

		// Whether or not to enable CSS page transitions
		//(see https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API#browser_compatibility for browser compatibility)
		'enablePageTransitions' => false,

		// Whether or not to show the "Made a mistake" function on the order completed page
		// If disabled then the heading and text will not be displayed
		'enableMadeAMistake' => false,

		// Whether to show the line item options
		'enableLineItemOptions' => '_',

		// The label to display for the estimated tax on the cart summary
		'estimatedTaxLabel' => 'My Estimated tax',

		// The label to display for the tax on the cart summary
		'taxLabel' => 'My Tax',
	],
	// Branding Settings
	'branding' => [
		// The brand primary custom color in HEX color
		'color' => '#333333',

		// The Google web font (https://fonts.google.com/) family name you want to use
		// (ex. 'Roboto Slab')
		'font' => 'Roboto Slab',

		// The relative path from the web root of the logo file
		// (ex. '/assets/images/logo.svg')
		'logo' => '',

		// The general component styles. Either 'rounded' (default) or 'flat'
		'style' => 'rounded',

		// The first part of the text in the title meta tag.
		// Leave blank to use the Craft's siteName
		'title' => '',
	],

	// Path Settings
	'paths' => [
		// The site relative path to where the cart should be accessible
		// (ex. 'cart')
		'cart' => '',

		// If true, use the cart template for the cart page, otherwise use the path only and let users define their own cart template.
		'useCartTemplate' => true,

		// The site relative path to where the checkout should be accessible
		// (ex. 'checkout')
		'checkout' => '',

		// The path the user should be taken to if they cancel the checkout process
		// (ex. '/')
		'cancel' => '',
	],

	// Product Settings
	'products' => [
		/*
		 * Add for each product type using the product type handle, to define the field handles used for the
		 * product and/or variant preview image to display in the cart view
		'shirts' => [
			productImageHandle => 'productPreviewImage',
			variantImageHandle => 'variantPreviewImage',
		]
		*/
	],

	// Notes that will appear in the cart, login, and checkout steps. Include the element handle (global or single)
	// and the field handle in that entry that contains the content you want to display. Fields can be either plain
	// text of rich text fields like Redactor
	'notes' => [
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
		'mistakeHeading' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
		'mistakeText' => [
			'elementHandle' => '',
			'fieldHandle' => '',
		],
	],
];
