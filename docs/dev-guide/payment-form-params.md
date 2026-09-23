# Payment form parameters

Change what a gateway's payment form is rendered with, per order. The checkout builds the parameters it passes to the gateway's `getPaymentFormHtml()`, then hands them to this event before rendering.

The settings on each gateway at **Checkout -> Gateways** cover the keys merchants change for every order. Use the event when the change depends on who is buying or what is in the cart, or for a key those settings do not cover.

## Answering the event

```php
<?php

namespace modules\site;

use craft\commerce\stripe\gateways\PaymentIntents;
use fostercommerce\fostercheckout\events\DefinePaymentFormParamsEvent;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;

Event::on(
    Checkout::class,
    Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS,
    static function (DefinePaymentFormParamsEvent $definePaymentFormParamsEvent): void {
        if (! $definePaymentFormParamsEvent->gateway instanceof PaymentIntents) {
            return;
        }

        if ($definePaymentFormParamsEvent->order->getCustomer()?->fullName === null) {
            $definePaymentFormParamsEvent->params['elementOptions']['wallets']['link'] = 'never';
        }
    }
);
```

`params` holds whatever the checkout built for that gateway. For Stripe that includes `elementOptions`, which the Payment Element is created with; for PayPal the SDK URL options. Anything a gateway's own payment form template reads can be set here.

## Stripe and Link

Turn Link off for a gateway with **Include Link** at **Checkout -> Gateways**, which also removes the methods marked “Powered by Link”. That setting writes `elementOptions.wallets.link`. Use the event to decide per order, since it runs after the setting.
