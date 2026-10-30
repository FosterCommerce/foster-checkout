# Blocking payment

Stop an order from being paid at the checkout and tell the customer why. On both layouts, the checkout shows the reason in place of the payment form and the pay button.

Commerce's own payment events can refuse a payment, but only once the customer submits. This event fires when the checkout renders, so the checkout shows the reason before the customer fills in a card.

## Answering the event

```php
<?php

declare(strict_types=1);

namespace modules\site;

use fostercommerce\fostercheckout\events\DefinePaymentBlockEvent;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

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
	}
}
```

Leave `reason` null to allow payment. When several handlers set a reason, the checkout shows the last one set.

The single-page checkout fires the event again after every save. When no handler sets `reason`, it shows the payment form again, so a reason the customer can fix on the page, such as an empty required field, clears without a reload. The stepped checkout fires the event when its payment step renders.

## What it does not do

The event changes only what the checkout renders. Commerce still accepts a payment posted another way, such as from a custom template or a script. To refuse that payment, listen for `craft\commerce\services\Payments::EVENT_BEFORE_PROCESS_PAYMENT`.

## In Twig

`craft.fostercheckout.paymentBlock(cart)` returns the reason or null, for a custom template that shows the reason elsewhere.
