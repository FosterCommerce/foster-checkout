# Payment form parameters

Change what a gateway's payment form is rendered with, per order. The checkout builds the parameters it passes to the gateway's `getPaymentFormHtml()`, then hands them to this event before rendering.

**Extra parameters** at **Checkout -> Gateways** does the same for every order. Use the event when the change depends on who is buying or what is in the cart.

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

`params` holds whatever the checkout built for that gateway. For Stripe that includes `elementOptions`, which the Payment Element is created with; for PayPal the SDK options. Anything a gateway's own payment form template reads can be set here.

## Stripe and Link

Link is a Stripe wallet, not a payment method type. It is shown whenever the intent allows cards and Link is on in the Stripe Dashboard, and it carries Instant Bank Payments and Klarna with it. Restricting the intent's payment method types does not remove it; `elementOptions.wallets.link = 'never'` does, for that form only.
