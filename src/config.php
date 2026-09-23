<?php

/**
 * Foster Checkout config.php
 *
 * A template for the Foster Checkout settings. It does nothing on its own.
 *
 * Copy it to `config/foster-checkout.php` and uncomment only what a developer needs to pin.
 * A key present here overrides that setting for every environment and shows it read-only in the
 * control panel. Only the keys named here are pinned; every sibling stays editable.
 *
 * A list, such as `priorityCountries`, is pinned whole rather than merged with the stored value.
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

	// Whether or not to show the shipping estimator
	// 'enableEstimatedShipping' => false, // true|false

	// Whether or not to enable CSS page transitions
	//(see https://developer.mozilla.org/en-US/docs/Web/API/View_Transitions_API#browser_compatibility for browser compatibility)
	// 'enablePageTransitions' => false, // true|false

	// Whether or not to show Avalara's corrected shipping address
	// (needs the AvaTax plugin with its own address validation turned on)
	// 'enableAddressVerification' => false, // true|false

	// Whether the checkout reports to Klaviyo. Needs the Klaviyo Connect Plus plugin.
	// 'enableKlaviyoTracking' => false,

	// The Klaviyo list the subscribe checkbox adds customers to. Name an env var to keep each
	// environment on its own list. Edited at Checkout -> Features unless it is set here.
	// 'klaviyoListId' => '$KLAVIYO_LIST_ID',

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
	// ],

	// Address suggestions as customers type
	// 'addressLookup' => [
	// Whether to suggest addresses on the shipping address
	// 'enabled' => false, // true|false

	// Which service the suggestions come from
	// 'provider' => 'google', // google|loqate

	// An env var name, so the key stays out of project config
	// 'apiKey' => '$FC_ADDRESS_LOOKUP_KEY',
	// ],

	// How line items are shown in the cart and at checkout
	// 'lineItems' => [
	// Width of a cart line item's image in pixels, at desktop widths. Narrower screens use half of it.
	// 'imageSize' => 150,

	// How an image fills its square box. 'contain' shows the whole image, 'cover' crops it to fill.
	// 'imageFit' => 'contain', // contain|cover

	// Whether a line item with no image shows a "No Image" placeholder
	// 'enablePlaceholderImages' => false, // true|false

	// Whether each line item offers a "save for later" button
	// 'enableSaveForLater' => false, // true|false

	// Transform config passed to Imager X, when that plugin renders the line item images
	// 'imagerXConfig' => null,

	// Whether each line item shows its SKU
	// 'showLineItemSku' => true, // true|false

	// Whether each line item names how many are left in stock
	// 'showLineItemStock' => true, // true|false

	// Whether line item options are shown at all. Gates the prefix and the rules below.
	// 'enableLineItemOptions' => true, // true|false

	// Options whose name starts with this are never shown. Empty shows every option.
	// 'hiddenLineItemOptionPrefix' => '_',

	// Characters to keep of an option value. Null shows the whole value.
	// 'lineItemOptionValueMaxLength' => null,
	// ],

	// Rewrites applied to a line item option, in order, each testing the option as it was stored.
	// Setting these here takes the rules screen away from the merchant.
	// 'lineItemOptionRules' => [
	// [
	// 'condition' => ['conditionRules' => [
	// ['class' => \fostercommerce\fostercheckout\conditions\OptionNameConditionRule::class, 'operator' => '=', 'value' => 'blessing'],
	// ]],
	// 'setName' => 'Blessing Services',
	// 'setValue' => 'Yes',
	// ],
	// ],
	// Branding Settings
	// 'branding' => [
	// The brand primary custom color in HEX color
	// 'color' => '#1F2937',

	// The background color of the header in HEX color
	// 'headerBgColor' => '#F3F3F3',

	// Color of anything drawn on the header bar, such as the cart link
	// 'headerTextColor' => '#1F2937',

	// The Google web font (https://fonts.google.com/) family name you want to use
	// 'font' => 'Rubik',

	// The relative path from the web root of the logo file
	// (ex. '/assets/images/logo.svg')
	// 'logo' => '',

	// Height of the logo in the header, in pixels
	// 'logoHeight' => 40,

	// The general component styles. Either 'rounded' (default) or 'flat'
	// 'style' => 'rounded',

	// Whether a field's label sits inside the field or above it
	// 'labelStyle' => 'floating', // floating|stacked

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
	// 'useCartTemplate' => true, // true|false

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
	// step : See docs/dev-guide/custom-includes.md for the values
	// cart : The current Commerce cart/order
	// */
	// 'includes' => [
	// Relative path to the include in your template directory that will be injected into the document <head>
	// 'head' => '',
	// Relative path to the include in your template directory that will be injected before the end </body> tag
	// 'body' => '',
	// Relative path to the include rendered in its own container above the checkout summary
	// 'summary' => '',
	// ],

	// The handle of the field on Orders holding the note a customer leaves with their order
	// 'customerOrderNotesFieldHandle' => null,

	// Product Settings
	// 'products' => [
	// /*
	//  * Add for each product type using the product type handle, to define the field handles used for the
	//  * product and/or variant preview image to display in the cart view
	// 'shirts' => [
	// 'productImageHandle' => 'productPreviewImage',
	// 'variantImageHandle' => 'variantPreviewImage',
	// ]
	// */
	// ],
	// Products whose variants never ship, as a Commerce product condition. Their orders skip the
	// shipping address and method at checkout and count as not shippable for Commerce and Postie.
	// Build it at Checkout -> Addresses and copy the project config value, or leave it to the CP.
	// 'notShippableProducts' => [],

	// Payment Gateways: keyed by the payment gateway handle configured in Craft Commerce.
	// Which fields a gateway asks for is a field layout, edited at Checkout -> Gateways, not config.
	// A note may be a closure when it needs to be computed; otherwise it is edited at Checkout -> Notes & Links.
	// Example:
	// /*
	// 'myGatewayHandle' => [
	// What the checkout calls the gateway. Leave it out to use the gateway's own name.
	// 'label' => 'Secure payment',
	// How Stripe's payment element lists the payment methods. Stripe gateways only.
	// 'layout' => 'tabs', // tabs|accordion|auto
	// Whether Stripe offers Link and the methods marked "Powered by Link". Stripe gateways only.
	// 'enableLink' => true, // true|false
	// Stripe payment method types in the order the payment element lists them. Stripe gateways only.
	// 'paymentMethodOrder' => ['card', 'apple_pay', 'google_pay', 'affirm'],
	// Funding sources PayPal leaves off its buttons, and ones it adds. PayPal gateways only.
	// 'disableFunding' => ['paylater', 'credit'],
	// 'enableFunding' => ['venmo'],
	// Card brands PayPal's own card fields turn away. PayPal gateways only, and PayPal has deprecated it.
	// 'disableCard' => ['amex'],
	// The language PayPal's buttons use. Empty lets PayPal pick from the buyer's country. PayPal gateways only.
	// 'locale' => 'en_US',
	// PayPal SDK components to load. Empty loads PayPal's own default. PayPal gateways only.
	// 'components' => 'buttons,messages',
	// 'note' => static fn (array $context): string => 'Computed note',
	// ]
	// */
	// 'paymentGateways' => [],

	// An array of gateway handles that should handle zero value orders
	// 'zeroValueGatewayHandles' => [],

	// An array of country codes that will be shown first in the country select dropdowns
	// Country a new address starts on, as a two-letter code. Empty asks with nothing chosen.
	// 'defaultCountryCode' => '',

	// 'priorityCountries' => [],

	// How many of the customer's saved addresses the checkout offers, most recently updated first.
	// Their primary address is always among them. Zero offers every address a customer has saved.
	// 'savedAddressLimit' => 10,

	// Handle of the address field holding a phone number, so its input asks for a phone keypad.
	// 'addressPhoneFieldHandle' => null,

	// Address fields to hide from checkout address forms, named by attribute or custom field handle.
	// A field the address layout marks required is always shown, since Craft needs it to validate
	// the address.
	// (ex. ['organization', 'organizationTaxId', 'addressNotes'])
	// 'hiddenAddressFields' => [],

	// Address fields to hide from the new billing address form as well, on top of the hidden list
	// (ex. ['phone', 'deliveryInstructions'])
	// 'hiddenBillingAddressFields' => [],

	// Additional address fields to require on a shipping address, and on a saved address a customer
	// edits, named by attribute or custom field handle. A hidden field is never required.
	// (ex. ['fullName'])
	// 'requiredAddressFields' => [],

	// The same for a new billing address
	// (ex. ['fullName'])
	// 'requiredBillingAddressFields' => [],

	// Whether checkout address forms offer a third address line for the countries whose format uses one
	// 'showAddressLine3' => false, // true|false

	// Whether a customer editing one of their saved addresses at the checkout can name it
	// 'showAddressLabelField' => false, // true|false

	// Whether a saved address the customer named is shown by that name in the address choices. An
	// order's own addresses and the store location are left out
	// 'showAddressLabelInPreview' => false, // true|false

	// Whether the shipping address choices include the store location, for orders collected there
	// 'enableCustomerPickup' => false, // true|false

	// Text of the pickup choice. Null shows "Customer pickup"
	// 'customerPickupLabel' => null,
];
