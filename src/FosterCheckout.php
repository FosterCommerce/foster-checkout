<?php

namespace fostercommerce\craftfostercheckout;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use fostercommerce\craftfostercheckout\models\Settings;
use fostercommerce\craftfostercheckout\services\Checkout;
use fostercommerce\craftfostercheckout\variables\Variables;
use yii\base\Event;


/**
 * Foster Checkout plugin
 *
 * @method static FosterCheckout getInstance()
 * @method Settings getSettings()
 * @author Foster Commerce <support@fostercommerce.com>
 * @copyright Foster Commerce
 * @license MIT
 * @property-read Checkout $checkout
 */
class FosterCheckout extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = false;

    public function init()
    {
        parent::init();

        Craft::setAlias('@fostercheckout', __DIR__);

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->registerComponents();
            $this->attachEventHandlers();
            $this->registerCustomVariables();
        });


    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('foster-checkout/_plugin/settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function registerComponents(): void
    {
        $this->setComponents([
            'checkout' => Checkout::class,
        ]);
    }

    private function registerCustomVariables(): void
    {
        // Register the variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function (Event $event): void {
                $variable = $event->sender;
                $variable->set('fostercheckout', Variables::class);
            }
        );
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;

                // Attach a service:
                $variable->set('checkout', Checkout::class);
            }
        );

        /* Register our plugins templates directory so Craft knows to look there  */
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['foster-checkout'] = __DIR__ . '/templates';
            }
        );

        /* Register our site URL rules based on the plugins 'paths' setting */
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                // Get the paths from the settings
                $paths = $this->checkout->paths();
                $checkoutPath = $paths['checkout'] ?? 'checkout';
                $cartPath = $paths['cart'] ?? 'cart';

                // Define the site URL rules to route to our plugins templates
                $event->rules[$checkoutPath] = [
                    'template' => 'foster-checkout/checkout/index',
                ];
                $event->rules[$checkoutPath . '/email'] = [
                    'template' => 'foster-checkout/checkout/email',
                ];
                $event->rules[$checkoutPath . '/address'] = [
                    'template' => 'foster-checkout/checkout/address',
                ];
                $event->rules[$checkoutPath . '/shipping'] = [
                    'template' => 'foster-checkout/checkout/shipping',
                ];
                $event->rules[$checkoutPath . '/payment'] = [
                    'template' => 'foster-checkout/checkout/payment',
                ];
                $event->rules[$checkoutPath . '/order'] = [
                    'template' => 'foster-checkout/checkout/order',
                ];
                $event->rules[$checkoutPath . '/login'] = [
                    'template' => 'foster-checkout/account/login',
                ];
                $event->rules[$checkoutPath . '/register'] = [
                    'template' => 'foster-checkout/account/register',
                ];
                $event->rules[$cartPath] = [
                    'template' => 'foster-checkout/cart/index',
                ];

                // TODO : Just a page to test components in isolation (remove later)
                $event->rules[$checkoutPath . '/components'] = [
                    'template' => 'foster-checkout/_component-test',
                ];
            }
        );
    }
}
