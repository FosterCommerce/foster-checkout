# Newsletter services

Subscribe customers who tick the newsletter checkbox through Mailchimp or any other service. With Klaviyo Connect 7.3.0 or later installed and a list ID set, the checkout subscribes them to a Klaviyo list without code; see [plugin integrations](../reference/integrations.md#klaviyo-connect).

## Listen for the event

`Checkout::EVENT_NEWSLETTER_SUBSCRIBE` fires when the customer submits their email with the newsletter checkbox ticked. It can fire more than once for the same customer, for example when they return to the email step. Subscribe them in the handler, then set `handled`:

```php
<?php

namespace modules\site;

use fostercommerce\fostercheckout\events\NewsletterSubscribeEvent;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;

Event::on(
    Checkout::class,
    Checkout::EVENT_NEWSLETTER_SUBSCRIBE,
    static function (NewsletterSubscribeEvent $newsletterSubscribeEvent): void {
        Mailchimp::subscribe($newsletterSubscribeEvent->listId, $newsletterSubscribeEvent->email);
        $newsletterSubscribeEvent->handled = true;
    }
);
```

`Mailchimp::subscribe()` stands in for your own service call.

| Property | What it holds |
| --- | --- |
| `email` | The email the customer entered |
| `listId` | **Checkout -> Features -> Newsletter list ID**, with an environment variable name resolved. Null when blank |
| `order` | The cart being checked out |
| `handled` | Set to `true` once subscribed, so Klaviyo Connect does not also subscribe the customer |

`listId` is optional for a handler. Leave the setting blank and pick the list in code, or set it to an audience ID or an environment variable name such as `$NEWSLETTER_LIST_ID`.

## When the checkbox is shown

The checkout shows the newsletter checkbox when all of these hold:

- **Newsletter checkbox** is on at **Checkout -> Features**.
- The checkbox's label is set at **Checkout -> Notes & Links**.
- A handler listens for `Checkout::EVENT_NEWSLETTER_SUBSCRIBE`, or Klaviyo Connect 7.3.0 or later is installed with a list ID set.
- Nobody is signed in, or the signed-in user's email is the order's. See [checkout for another customer](./checkout-for-another-customer.md).

On the stepped checkout, a signed-in customer sees the email step only when the checkbox is shown.

`craft.fostercheckout.offersNewsletter(cart)` returns the same answer.
