<?php

namespace fostercommerce\craftfostercheckout;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use fostercommerce\craftfostercheckout\models\Settings;
use fostercommerce\craftfostercheckout\services\Checkout;
use fostercommerce\craftfostercheckout\services\Users;

use craft\commerce\events\CustomizeVariantSnapshotDataEvent;
use craft\commerce\events\CustomizeProductSnapshotDataEvent;
use craft\commerce\events\CustomizeVariantSnapshotFieldsEvent;
use craft\commerce\elements\Variant;

use craft\commerce\events\LineItemEvent;
use craft\commerce\services\LineItems;
use craft\commerce\models\LineItem;

/**
 * Foster Checkout plugin
 *
 * @method static FosterCheckout getInstance()
 * @method Settings getSettings()
 * @author Foster Commerce <support@fostercommerce.com>
 * @copyright Foster Commerce
 * @license MIT
 * @property-read Checkout $checkout
 * @property-read Users $users
 */
class FosterCheckout extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = false;

    public function init()
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->registerComponents();
            $this->attachEventHandlers();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('foster-checkout/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function registerComponents(): void
    {
        $this->setComponents([
            'checkout' => Checkout::class,
            'users' => Users::class
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;

                // Attach a service:
                $variable->set('checkout', Checkout::class);
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $paths = $this->checkout->paths();
                $checkoutPath = $paths['checkout'] ?? 'checkout';
                $cartPath = $paths['cart'] ?? 'cart';
                $event->rules[$checkoutPath] = ['template' => 'foster-checkout/checkout'];
                $event->rules[$checkoutPath . '/order'] = ['template' => 'foster-checkout/checkout/order'];
                $event->rules[$cartPath] = ['template' => 'foster-checkout/cart'];
            }
        );

    }
}
