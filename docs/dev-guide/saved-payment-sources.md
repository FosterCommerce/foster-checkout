# Saved payment methods per user

Change which saved payment methods the checkout lists for the signed-in user, and whether it shows the **Save this payment method for future purchases** checkbox. The checkout lists the order customer's saved payment methods for the gateway, then triggers this event with the list before rendering. The checkout triggers the event only for a signed-in user paying through a Stripe gateway, while **Enable saved payment methods** at **Checkout -> Features** is on.

Use the event when the order's customer is not the person paying, such as a company account that several buyers share.

## Answering the event

```php
<?php

namespace modules\site;

use Craft;
use fostercommerce\fostercheckout\events\DefineSavedPaymentSourcesEvent;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;

Event::on(
    Checkout::class,
    Checkout::EVENT_DEFINE_SAVED_PAYMENT_SOURCES,
    static function (DefineSavedPaymentSourcesEvent $defineSavedPaymentSourcesEvent): void {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser?->id !== $defineSavedPaymentSourcesEvent->order->getCustomerId()) {
            $defineSavedPaymentSourcesEvent->paymentSources = [];
            $defineSavedPaymentSourcesEvent->canSave = false;
        }
    }
);
```

`paymentSources` is the list of the order customer's saved payment methods for `gateway`. Remove each payment method the signed-in user cannot use. Set `canSave` to `false` to hide the save checkbox.

## What it does not do

The event changes only what the checkout lists. Commerce still pays with a `paymentSourceId` that a request posts, when the payment method belongs to the order's customer. Refuse that payment in your module with `craft\commerce\services\Payments::EVENT_BEFORE_PROCESS_PAYMENT`.

## In Twig

`craft.fostercheckout.savedPaymentSources(cart, gateway)` returns the event after any handler has run, for a custom payment template. The checkout calls it only for a signed-in user while **Enable saved payment methods** is on, so wrap the call in the same check:

```twig
{% if currentUser and craft.fostercheckout.settings.enableSavedPaymentSources %}
    {% set savedPaymentSources = craft.fostercheckout.savedPaymentSources(cart, gateway) %}
    {% for paymentSource in savedPaymentSources.paymentSources %}
        {{ paymentSource.description }}
    {% endfor %}
    {% if savedPaymentSources.canSave %}
        {% include 'foster-checkout/_components/app/save-payment-source-checkbox' %}
    {% endif %}
{% endif %}
```
