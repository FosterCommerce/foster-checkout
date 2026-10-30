# Payment form parameters

Change the parameters a gateway's payment form renders with, per order. The checkout builds the parameters it passes to the gateway's `getPaymentFormHtml()`, then fires this event with them before rendering.

The settings for each gateway at **Checkout -> Gateways** apply to every order. Use the event when a change depends on the customer or the cart, or for a key those settings do not cover.

## Answering the event

```php
<?php

declare(strict_types=1);

namespace modules\site;

use craft\commerce\stripe\gateways\PaymentIntents;
use fostercommerce\fostercheckout\events\DefinePaymentFormParamsEvent;
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
	}
}
```

`params` holds the parameters the checkout built for that gateway. For Stripe, they include `elementOptions`, the options the checkout creates Stripe's payment element with. For PayPal, they include the SDK URL options. For every other gateway except Manual and Mollie, `params` holds `order`, `submitButtonText` and `submitButtonClasses`. You can set any key the gateway's payment form template reads.

For a Mollie gateway, `params` holds `paymentMethods`, the list of methods the checkout shows. Each method has `id`, `name`, `image` (an SVG URL) and `issuers` (each with `id` and `name`). The checkout renders that list itself and ignores every other key. On the single-page checkout, the event fires for each available Mollie gateway when the page loads and after every save.

## Stripe and Link

To turn off Link for a gateway, turn off **Include Link** at **Checkout -> Gateways**. The setting also removes the methods marked “Powered by Link”, and writes `elementOptions.wallets.link`. To decide per order, use the event, because the checkout fires the event after applying the setting.

## In Twig

`craft.fostercheckout.molliePaymentMethods(cart, gateway)` returns a Mollie gateway's list of methods without this event's changes. It returns an empty list when the request to Mollie fails, or when the checkout skips the request after a failure.
