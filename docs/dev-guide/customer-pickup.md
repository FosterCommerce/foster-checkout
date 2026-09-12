# Detecting a pickup order

A pickup order ships to the Commerce store location. No flag is stored on the order; the plugin recognizes one by comparing the shipping address to that location.

## In PHP

`isCustomerPickup()` on the checkout service answers for any order. A module can use it to leave carrier rates off a pickup order, here with Postie:

```php
<?php

namespace modules\site;

use fostercommerce\fostercheckout\FosterCheckout;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\services\Service as PostieService;
use yii\base\Event;

Event::on(
    PostieService::class,
    PostieService::EVENT_BEFORE_REGISTER_SHIPPING_METHODS,
    static function (ModifyShippingMethodsEvent $modifyShippingMethodsEvent): void {
        $isCustomerPickup = FosterCheckout::getInstance()?->getCheckout()->isCustomerPickup($modifyShippingMethodsEvent->order);

        if ($isCustomerPickup) {
            $modifyShippingMethodsEvent->shippingMethods = [];
        }
    }
);
```

## In Twig

```twig
{% if craft.fostercheckout.isCustomerPickup(cart) %}
    <p>Collect this order from our warehouse.</p>
{% endif %}
```

## In the cart response

The single-page checkout's cart JSON carries the same answer as `fosterCheckout.customerPickup`, alongside the shipping methods and totals.

See the [user guide](../user-guide/customer-pickup.md) for turning pickup on and pricing it.
