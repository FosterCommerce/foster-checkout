<?php

namespace fostercommerce\craftfostercheckout\models;

use Craft;
use craft\base\Model;

/**
 * Foster Checkout settings
 */
class Settings extends Model
{
    public array $branding = [
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
        'logo' => '',
        'header' => '',
        'footer' => '',
        'css' => '',
        'js' => '',
    ];

    public array $paths = [
        'checkout' => '',
        'cart' => '',
        'cancel' => ''
    ];

    public array $products =[];
}
