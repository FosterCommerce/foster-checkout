<?php

namespace fostercommerce\craftfostercheckout\services;

use Craft;

use craft\commerce\Plugin;
use fostercommerce\craftfostercheckout\FosterCheckout;
use yii\base\Component;

use craft\commerce\elements\Variant;

/**
 * Checkout service
 */
class Checkout extends Component
{
    /*
     * Gets the branding settings array
     */
    public function branding(): array
    {
        $settings = FosterCheckout::getInstance()->getSettings();
        return $settings->branding;
    }

    /*
     * Gets the paths settings array
     */
    public function paths(): array
    {
        $settings = FosterCheckout::getInstance()->getSettings();
        return $settings->paths;
    }

    /*
     * Gets a product type setting based on the type handle
     */
    public function productFields($handle = null): ?array
    {
        
        $settings = FosterCheckout::getInstance()->getSettings();

        if ($handle) {

            return $settings->products[$handle] ?? null;

        } else {

            return $settings->products;

        }

    }

    public function getVariants($ids): array {

        $variants = [];
        $fields = $this->productFields('general');

        $results = Variant::find()
            ->id($ids)
            ->with([
                $fields['variantImageField'],
                "product." . $fields['previewImageField']
            ])
            ->all(); 

        foreach ($results as $variant) $variants[$variant['id']] = $variant;

        // May need to test whether anything returned is a query.

        return $variants;

    }

    public function getCountries(): array
    {
        return Plugin::getInstance()->getStore()->getStore()->getCountriesList();
    }

    public function getRegions(): array
    {
        return Plugin::getInstance()->getStore()->getStore()->getAdministrativeAreasListByCountryCode();
    }

    public function getDiscounts(): array
    {
        return Plugin::getInstance()->getDiscounts()->allDiscounts;
    }
}
