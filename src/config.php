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

        // The brand primary custom color and shades in HEX color
        // (use https://www.tailwindshades.com/) to get tint values
        'color' => [
            '100' => '',
            '200' => '',
            '300' => '',
            '400' => '',
            '500' => '',
            '600' => '',
            '700' => '',
            '800' => '',
            '900' => '',
        ],

        // The sites background color in HEX (usually the sites header or footer color)
        // (ex. '#000000') - If left blank the background will be white
        'background' => '',

        // The Google web font (https://fonts.google.com/) family name you want to use
        // (ex. 'Roboto Slab')
        'font' => '',

        // The relative path from the web root of the logo file
        // (ex. '/assets/images/logo.svg')
        'logo' => '',

        // The relative path from your templates folder of your sites custom header partial
        // (ex. '/components/global/header')
        'header' => '',

        // The relative path from your templates folder of your sites custom footer partial
        // (ex. '/components/global/footer')
        'footer' => '',

        // The relative path from your web root of your sites CSS (to support your custom header and footer)
        // (ex. '/asset/css/style.css')
        'css' => '',

        // The relative path from your web root of your sites Javascript (to support your custom header and footer)
        // (ex. '/asset/js/script.js')
        'js' => '',
    ],

    // Path Settings
    'paths' => [

        // The site relative path to where the checkout should be accessible
        // (ex. 'checkout')
        'checkout' => '',

        // The site relative path to where the cart should be accessible
        // (ex. 'cart')
        'cart' => '',

        // The path the user should be taken to if they cancel the checkout process
        // (ex. '/')
        'cancel' => ''
    ],

    // Product Settings
    'products' => [
        /*
        // Add for each product type using their handle, to define the field handle used for its preview image
        'shirts' => [
            'previewImageField' => 'assetFieldHandleHere'
        ],
        */
    ]
];