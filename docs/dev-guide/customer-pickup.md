# Detecting a pickup order

A pickup order ships to the Commerce store location. The plugin does not store a pickup flag on the order. It detects a pickup order by comparing the shipping address to the store location.

## In PHP

`isCustomerPickup()` on the checkout service returns whether an order is a pickup order. It returns false whenever **Enable customer pickup** at **Checkout -> Features** is off. A module can use it to leave carrier rates off a pickup order, as in this Postie example:

```php
<?php

declare(strict_types=1);

namespace modules\site;

use craft\commerce\elements\Order;
use fostercommerce\fostercheckout\FosterCheckout;
use verbb\postie\events\ModifyShippingMethodsEvent;
use verbb\postie\services\Service as PostieService;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

		Event::on(
			PostieService::class,
			PostieService::EVENT_BEFORE_REGISTER_SHIPPING_METHODS,
			static function (ModifyShippingMethodsEvent $modifyShippingMethodsEvent): void {
				$order = $modifyShippingMethodsEvent->order;

				if ($order instanceof Order && FosterCheckout::getInstance()?->getCheckout()->isCustomerPickup($order)) {
					$modifyShippingMethodsEvent->shippingMethods = [];
				}
			}
		);
	}
}
```

## In Twig

```twig
{% if craft.fostercheckout.isCustomerPickup(cart) %}
	<p>Collect this order from our warehouse.</p>
{% endif %}
```

## In the cart response

The single-page checkout's cart JSON includes the same value as `fosterCheckout.customerPickup`, next to the shipping methods and totals.

To turn on pickup and set its price, see [customer pickup](../user-guide/customer-pickup.md).
