# Newsletter services

Subscribe customers who select the newsletter checkbox to Mailchimp or another newsletter service. With Klaviyo Connect 7.3.0 or later installed and a list ID set, the checkout subscribes them to a Klaviyo list without code. For that setup, see [Klaviyo Connect](../reference/integrations.md#klaviyo-connect).

## Listen for the event

`Checkout::EVENT_NEWSLETTER_SUBSCRIBE` fires when the customer submits their email with the newsletter checkbox selected. It can fire more than once for the same customer, for example when they return to the email step. Subscribe them in the handler, then set `handled`:

```php
<?php

declare(strict_types=1);

namespace modules\site;

use fostercommerce\fostercheckout\events\NewsletterSubscribeEvent;
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
			Checkout::EVENT_NEWSLETTER_SUBSCRIBE,
			static function (NewsletterSubscribeEvent $newsletterSubscribeEvent): void {
				Mailchimp::subscribe($newsletterSubscribeEvent->listId, $newsletterSubscribeEvent->email);
				$newsletterSubscribeEvent->handled = true;
			}
		);
	}
}
```

`Mailchimp::subscribe()` stands in for your own service call.

| Property | Value |
| --- | --- |
| `email` | The email the customer entered |
| `listId` | **Newsletter list ID** at **Checkout -> Features**, with an environment variable name resolved. Null when blank |
| `order` | The cart being checked out |
| `handled` | Set to `true` after your handler subscribes the customer, so Klaviyo Connect does not subscribe them a second time |

`listId` is optional for a handler. Leave the setting blank and pick the list in code, or set it to an audience ID or an environment variable name such as `$NEWSLETTER_LIST_ID`.

## When the checkbox is shown

The checkout shows the newsletter checkbox when all of these are true:

- **Newsletter checkbox** is on at **Checkout -> Features**.
- **Newsletter checkbox label** is set at **Checkout -> Notes & Links**.
- A handler listens for `Checkout::EVENT_NEWSLETTER_SUBSCRIBE`, or Klaviyo Connect 7.3.0 or later is installed with a list ID set.
- Nobody is signed in, or the signed-in user's email is the order's. See [checkout for another customer](./checkout-for-another-customer.md).

On the stepped checkout, a signed-in customer sees the email step only when the checkout shows the checkbox.

`craft.fostercheckout.offersNewsletter(cart)` returns whether the checkout shows the checkbox.
