<?php
/**
 * Foster Checkout
 *
 * A companion plugin to the Foster Checkout Commerce templates.
 *
 * @link      https://fostercommerce.com
 * @copyright Copyright (c) 2023 Foster Commerce
 */

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
    ],

    // Path Settings
    'paths' => [

        // The site relative path to where the cart should be accessible
        // (ex. 'cart')
        'cart' => '',

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
        // Add for each product type using the product type handle, to define the field handles used for the
        // product and/or variant preview image to display in the cart view
        'shirts' => [
            'image' => [
                'product' => 'productPreviewImage',
                'variant' => 'variantPreviewImage',
            ],
        ],
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
    ],
];
