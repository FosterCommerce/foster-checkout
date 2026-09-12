# Blocking payment

Stop an order from being paid at the checkout and tell the customer why. The payment form and the pay button are not rendered; the reason is shown in their place on both layouts.

Commerce's own payment events can refuse a payment, but only once the customer submits. This hook runs when the checkout renders, so a customer who cannot pay is told before filling in a card.

## Answering the event

```php
<?php

namespace modules\site;

use fostercommerce\fostercheckout\events\DefinePaymentBlockEvent;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;

Event::on(
    Checkout::class,
    Checkout::EVENT_DEFINE_PAYMENT_BLOCK,
    static function (DefinePaymentBlockEvent $definePaymentBlockEvent): void {
        $customer = $definePaymentBlockEvent->order->getCustomer();

        if ($customer !== null && ! $customer->isCredentialed) {
            $definePaymentBlockEvent->reason = 'Activate your account before paying.';
        }
    }
);
```

Leave `reason` null to allow payment. Several handlers may run; the last to set a reason wins.

## What it does not do

The hook is a checkout rendering decision. A payment posted another way, from a custom template or a script, still reaches Commerce, so keep refusing it there too, with `craft\commerce\services\Payments::EVENT_BEFORE_PROCESS_PAYMENT`.

## In Twig

`craft.fostercheckout.paymentBlock(cart)` returns the reason or null, for a template that wants to show it elsewhere.
