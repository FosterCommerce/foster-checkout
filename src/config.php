<?php

/**
 * Foster Checkout config.php
 *
 * A template for the Foster Checkout settings. It does nothing on its own.
 *
 * Copy it to `config/foster-checkout.php` and uncomment only what a developer needs to pin.
 * Any key present here overrides the setting for every environment and makes its control
 * panel section read-only, so an uncommented block takes that screen away from the merchant.
 *
 * Once copied, the file is multi-environment aware in the same way as `general.php`.
 */

return [
	// How the checkout content edited in the control panel varies across sites.
	// 'none'     - one copy shared by every site
	// 'site'     - a copy per site
	// 'language' - sites speaking the same language share a copy
	// 'contentTranslationMethod' => 'site', // none|site|language

	// Plugin options
	// 'options' => [
	// Whether to serve the single-page checkout. Existing sites stay on multi-page until this is turned on.
	// 'enableSinglePageCheckout' => false, // true|false

	// Whether or not to show the "save for later" button
	// 'enableSaveForLater' => false, // true|false

	// Whether or not to show the shipping estimator

	// Whether or not to show the free shipping message

	// Whether or not to show the "No Image" placeholder images
	// 'enablePlaceholderImages' => false,

	// Whether or not to enable CSS page transitions
	//(see https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API#browser_compatibility for browser compatibility)
	// 'enablePageTransitions' => false,

	// Whether or not to show the "Made a mistake" function on the order completed page
	// If disabled then the heading and text will not be displayed
	// 'enableMadeAMistake' => false,

	// Whether to show the line item options
	// 'enableLineItemOptions' => '_',

	// The Klaviyo list ID to subscribe the customer to
	// 'klaviyoListId' => null,

	// The text to display for the subscribe checkbox. Can also be a plain string, or a callable which returns a string
	// 'subscribe' => [
	// 'elementHandle' => '',
	// 'fieldHandle' => '',
	// ],

	// 'deliveryDate' => [
	// 'label' => 'Expected delivery date',
	// 'message' => 'Please note: Our support team is unable to investigate delivery issues until after the estimated delivery date has passed.',
	// 'estimate' => '{{ order.dateOrdered|date_modify("+14 days")|date("M j, Y") }}', // closure, twig, string or null
	// 'display' => true, // closure, twig or a boolean
	// ],

	// The field handle on the Order element that will contain the payment due date.
	// When the due date is set, and the order is not fully paid, the order confirmation page will show the payment due date.
	// 'paymentDueDateFieldHandle' => null,

	// 'imagerXConfig' => null,
	// ],
	// Branding Settings
	// 'branding' => [
	// The brand primary custom color in HEX color
	// 'color' => '#333333',

	// The background color of the header in HEX color
	// 'headerBgColor' => '#F3F3F3',

	// The Google web font (https://fonts.google.com/) family name you want to use
	// (ex. 'Roboto Slab')
	// 'font' => 'Roboto Slab',

	// The relative path from the web root of the logo file
	// (ex. '/assets/images/logo.svg')
	// 'logo' => '',

	// The general component styles. Either 'rounded' (default) or 'flat'
	// 'style' => 'rounded',

	// The first part of the text in the title meta tag.
	// Leave blank to use the Craft's siteName
	// 'title' => '',

	//  Array of paths to favicons for use in the cart/checkout
	// 'faviconConfig' => [
	// 'faviconIco' => '',
	// 'favicon32' => '',
	// 'favicon16' => '',
	// 'appleTouchIcon' => '',
	// 'maskIcon' => '',
	// 'maskIconColor' => '',
	// 'manifestUrl' => '',
	// 'msTileColor' => '',
	// 'themeColor' => '',
	// ],
	// ],

	// Path Settings
	// 'paths' => [
	// The site relative path to the account page, linked from the completed checkout steps
	// (ex. 'account')
	// 'account' => '',

	// The site relative path to where the cart should be accessible
	// (ex. 'cart')
	// 'cart' => '',

	// If true, use the cart template for the cart page, otherwise use the path only and let users define their own cart template.
	// 'useCartTemplate' => true,

	// The site relative path to where the checkout should be accessible
	// (ex. 'checkout')
	// 'checkout' => '',

	// The path the user should be taken to if they cancel the checkout process
	// (ex. '/')
	// 'cancel' => '',
	// ],

	// /*	Custom Includes
	// Paths to twig includes/partials in your templates directory which will be injected into
	// the cart and checkout pages. Within these includes the following variables will be available :
	// context : Either "cart" or "checkout"
	// location : Either "head" or "body"
	// step : Either "email", "shipping-address", "shipping-method", "payment", "confirmation" or an empty string
	// cart : The current Commerce cart/order
	// */
	// 'includes' => [
	// Relative path to the include in your template directory that will be injected into the document <head>
	// 'head' => '',
	// Relative path to the include in your template directory that will be injected before the end </body> tag
	// 'body' => '',
	// ],

	// The handle of the field on Orders holding the note a customer leaves with their order
	// 'customerOrderNotesFieldHandle' => null,

	// Product Settings
	// 'products' => [
	// /*
	//  * Add for each product type using the product type handle, to define the field handles used for the
	//  * product and/or variant preview image to display in the cart view
	// 'shirts' => [
	// productImageHandle => 'productPreviewImage',
	// variantImageHandle => 'variantPreviewImage',
	// ]
	// */
	// ],
	// Payment Gateways: keyed by the payment gateway handle configured in Craft Commerce.
	// Which fields a gateway asks for is a field layout, edited at Checkout -> Gateways, not config.
	// A note may be a closure when it needs to be computed; otherwise it is edited at Checkout -> Content.
	// Example:
	// /*
	// 'myGatewayHandle' => [
	// 'note' => static fn (array $context): string => 'Computed note',
	// 'params' => [
	// Extra params merged into the gateway's payment form params.
	// For PayPal Checkout, these become PayPal SDK URL options (e.g. disable-funding, enable-funding, intent).
	// 'disable-funding' => 'paylater,credit',
	// ],
	// ]
	// */
	// 'paymentGateways' => [],

	// An array of gateway handles that should handle zero value orders
	// 'zeroValueGatewayHandles' => [],

	// An array of country codes that will be shown first in the country select dropdowns
	// 'priorityCountries' => [],

	// Address fields to hide from checkout address forms, named by attribute or custom field handle.
	// A field the address layout marks required is always shown, since Craft needs it to validate
	// the address.
	// (ex. ['organization', 'organizationTaxId', 'addressNotes'])
	// 'hiddenAddressFields' => [],

	// Additional address fields to require on checkout address forms, named by attribute or custom
	// field handle. A hidden field is never required, since it is not rendered.
	// (ex. ['fullName'])
	// 'requiredAddressFields' => [],
];
