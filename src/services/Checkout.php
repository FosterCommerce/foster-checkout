<?php

namespace fostercommerce\craftfostercheckout\services;

use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\errors\InvalidFieldException;
use fostercommerce\craftfostercheckout\FosterCheckout;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Checkout service
 *
 * @property-read array $gateways
 * @property-read array $regions
 * @property-read string $paymentForm
 * @property-read array $discounts
 * @property-read array $countries
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

    /**
     * Gets the options settings
     */
    public function options(): array
    {
        $settings = FosterCheckout::getInstance()->getSettings();
        return $settings->options;
    }

    /**
     * Gets the value of a single option from the settings
     */
    public function option(string $option): string|bool|null
    {
        $settings = FosterCheckout::getInstance()->getSettings();
        return $settings->options[$option] ?? null;
    }

    /*
     * Gets the 'dist' javascript asset bundle from the plugin
     * Note: We are getting it this way as running view.registerAssetBundle() in the template does not output the
     * script tag with type="module" attribute
    */
    public function jsBundle(): string
    {
        return \Craft::$app->assetManager->getPublishedUrl('@fostercheckout/web/assets/checkout/dist/js/app.js', true);
    }

    /*
     * Gets the custom note data based on the template page we are on
    */
    public function note($page): ?string
    {
        // Get the data in the settings
        $settings = FosterCheckout::getInstance()->getSettings();
        $notesArr = $settings->notes;
        $elementHandle = $notesArr[$page]['elementHandle'] ?? null;
        $fieldHandle = $notesArr[$page]['fieldHandle'] ?? null;

        $note = null;

        if ($elementHandle && $fieldHandle) {
            $global = GlobalSet::find()->handle($elementHandle)->one() ?? null;
            $entry = Entry::find()->section($elementHandle)->one() ?? null;
            $element = $global ?? $entry;

            // Get the content field data and parse it if necessary (for rich text fields like Redactor)
            if ($element) {
                try {
                    $content = $element->getFieldValue($fieldHandle);
                    if ($content) {
                        $note = method_exists($content, 'getParsedValue') ? $content->getParsedValue() : $content;
                    } else {
                        $note = null;
                    }
                } catch (InvalidFieldException) {
                    $note = null;
                }
            }
        }

        return $note;
    }

    /**
     * Gets the line items image field based on the products settings
     */
    public function lineItemImageField($productType): ?array
    {
        $settings = FosterCheckout::getInstance()->getSettings();
        $products = $settings->products;
        $fieldData = null;

        if (is_string($productType)) {
            if (! empty($products[$productType]['image']['product'])) {
                $fieldData = [
                    'handle' => $products[$productType]['image']['product'],
                    'level' => 'product',
                ];
            }

            if (! empty($products[$productType]['image']['variant'])) {
                $fieldData = [
                    'handle' => $products[$productType]['image']['variant'],
                    'level' => 'variant',
                ];
            }
        }

        return $fieldData;
    }

    /**
     * Gets a line items image asset based on the config settings for the product type
     *
     * @throws InvalidConfigException
     */
    public function lineItemImage(LineItem $lineItem): ?Asset
    {
        $image = null;
        $sku = $lineItem->getSku();
        $variant = Variant::find()->sku($sku)->one();
        /** @var Product $product */
        $product = $variant->getOwner();

        $fieldInfo = $this->lineItemImageField($product->type->handle);

        if ($fieldInfo !== null) {
            try {
                if ($fieldInfo['level'] === 'variant') {
                    $image = $variant->getFieldValue($fieldInfo['handle'])->one();
                } else {
                    $image = $product->getFieldValue($fieldInfo['handle'])->one();
                }
            } catch (InvalidFieldException) {
                // Do nothing
            }
        }

        return $image;
    }

    /*
     * Gets the available countries from Commerce
     */
    public function getCountries(): array
    {
        try {
            return Plugin::getInstance()->getStore()->getStore()->getCountriesList();
        } catch (InvalidConfigException) {
            return [];
        }
    }

    /*
     * Gets the available regions (administrative areas) from Commerce
     */
    public function getRegions(): array
    {
        try {
            return Plugin::getInstance()->getStore()->getStore()->getAdministrativeAreasListByCountryCode();
        } catch (InvalidConfigException) {
            return [];
        }
    }

    /*
     * Gets any discounts that are configured in Commerce
     */
    public function getDiscounts(): array
    {
        try {
            return Plugin::getInstance()->getDiscounts()->allDiscounts;
        } catch (InvalidConfigException) {
            return [];
        }
    }

    /*
     * Gets the available gateways configured in Commerce
     */
    public function getGateways(): array
    {
        try {
            $gateways = Plugin::getInstance()->gateways->getAllCustomerEnabledGateways();
        } catch (InvalidConfigException) {
            $gateways = [];
        }

        $gatewaysArr = [];
        foreach ($gateways as $gateway) {
            $gatewaysArr[] = [
                'id' => $gateway->id,
                'handle' => $gateway->handle,
                'name' => $gateway->name,
                'type' => $gateway->paymentType,
            ];
        }

        return $gatewaysArr;
    }

    /*
     * Outputs a gateways payment form HTML
     */
    public function getPaymentForm(): string
    {
        $cart = Plugin::getInstance()->getCarts()->getCart();

        return $cart->gateway->getPaymentFormHtml([
            'currency' => $cart->paymentCurrency,
        ]);
    }
}
