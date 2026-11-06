# Partial payments

Let a customer put part of an order on a Net Terms account and pay the rest with another gateway, or complete the order if the account covers it all.

## Set up the step

1. In **Checkout -> Gateways**, edit a Net Terms gateway and turn on **Use as a partial payment step**. The checkout then lists the gateway only as this step, not as a payment method. The gateway's payment type must be **Purchase (bill on invoices)**. With any other type, the checkout does not show the gateway at all.
2. Set the amount from `Checkout::EVENT_DEFINE_PARTIAL_PAYMENT`. The checkout shows the step when a handler sets an amount above zero. The signed-in customer must also be a buyer on an active account in the order's currency, with available credit.

## Answering the event

```php
<?php

declare(strict_types=1);

namespace modules\site;

use fostercommerce\fostercheckout\events\DefinePartialPaymentEvent;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\netterms\gateways\NetTerms;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

		Event::on(
			Checkout::class,
			Checkout::EVENT_DEFINE_PARTIAL_PAYMENT,
			static function (DefinePartialPaymentEvent $definePartialPaymentEvent): void {
				if (! $definePartialPaymentEvent->gateway instanceof NetTerms) {
					return;
				}

				$definePartialPaymentEvent->amount = $definePartialPaymentEvent->order->getTotalShippingCost();
				$definePartialPaymentEvent->label = '{amount} available toward shipping';
				$definePartialPaymentEvent->appliedLabel = '{amount} applied toward shipping';
			}
		);
	}
}
```

| Property | Type | Default | Notes |
|---|---|---|---|
| `order` | `Order` | | The cart, or the completed order on the confirmation page |
| `gateway` | `GatewayInterface` | | The gateway the step is for |
| `amount` | `?float` | `null` | The most this gateway can cover. The step shows the lowest of this amount less what the gateway has already applied, the balance left after applied payments, and the buyer's available credit |
| `summaryLabel` | `?string` | `null` | The summary row's name. Null uses the gateway's **Name shown to customers**, or its name if that is blank |
| `label` | `string` | `{amount}` | The text beside **Apply** |
| `appliedLabel` | `string` | `{amount} applied` | The text after the customer applies the payment |

`label` and `appliedLabel` replace `{amount}` with the formatted amount.

## What the customer does

Both checkout layouts show the partial payment step above the payment form.

| Action | Result |
|---|---|
| **Apply** | Authorizes the amount on the account, adds a summary row, and subtracts the amount from the Total the checkout shows. `order.totalPrice` does not change |
| **Remove** | Releases the authorization |
| **Delete this cart** or **Start a new cart** | Releases the authorization |
| Changes the cart | Resizes the authorization to the lowest of the event's amount, the outstanding balance and the buyer's available credit, or releases it at zero |
| Pays the rest with another gateway | Completes the order, and captures the authorization |
| **Complete order**, shown when the account covers the total | Completes the order, and captures the authorization |

If a capture fails, the order stays complete, and Foster Checkout saves a failed capture transaction. Foster Checkout also logs an exception thrown during capture.

## Transaction messages

Foster Checkout writes its own transaction messages in the primary site's language, such as “Authorization released by customer.” It stores a gateway's messages unchanged.

## Actions

To build your own controls, post to these actions. All three act on the current cart. `gatewayId` is the Net Terms gateway's ID, `accountId` is an account the customer is a buyer on, and `transactionId` is the ID of the applied payment's transaction.

| Action | Params |
|---|---|
| `foster-checkout/partial-payment/apply` | `gatewayId`, `paymentForm[accountId]`, `expectedAmount` |
| `foster-checkout/partial-payment/remove` | `gatewayId`, `transactionId` |
| `foster-checkout/partial-payment/complete` | none |

A request that accepts JSON returns `success`, `error`, `cart` and `redirect`, where `redirect` is the confirmation page's URL once the order completes. A failed operation returns `success: false` with a 200 status. Any other request redirects to the confirmation page once the order completes, including a post that was waiting while the order completed. Otherwise, it redirects to the posted `redirect` or the current page, and sets a flash message on failure. If there is no saved cart, or the cart stays locked for 5 seconds, a JSON request gets a 400 response with a `message`. Any other request gets the flash message.

`expectedAmount` is optional. **Apply** fails and asks the customer to review the totals when recalculating changes the total or quantity, or when `expectedAmount` no longer matches the amount available. **Apply** and **Complete order** fail if the checkout is missing the email, a required address or a required shipping method, or if a [payment block](./blocking-payment.md) is set. **Complete order** also fails unless applied payments cover the total. All three actions cancel an unfinished Stripe payment, and fail while any other payment is in progress.

If the customer is a buyer on one account, `paymentForm[accountId]` is optional. A `transactionId` that does not match an applied payment leaves the cart unchanged and still returns `success: true`.
