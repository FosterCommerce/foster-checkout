# Checkout for another customer

Running the checkout where the order's customer is not the person signed in, as a purchasing agent buying against a company account does.

Nothing here is switched on in the control panel. Your own code sets the order's customer; the checkout reads it.

## What follows the order's customer

Once `order.customer` is someone other than the signed-in user, these change on their own.

| What | Behavior |
| --- | --- |
| The address book | Listed from the order's customer, when Craft lets the signed-in user view an address owned by that customer |
| Picking a saved address | Resolved against the order customer's book, since Commerce looks a posted id up on the signed-in user |
| A saved address | Owned by the order's customer, so it lands in the company's book |
| Editing and saving addresses | Offered only when Craft lets the signed-in user save an address owned by that customer |
| Klaviyo events | Not sent, so they are not filed under someone who is not the shopper |

The checkout also names the person signed in, below the contact, whenever their email differs from the order's.

## The contact shown

`craft.fostercheckout.contact(order)` returns what the checkout prints as the contact. It is the order's email unless a handler sets something else, which is how a company name or an account reference gets there in place of a shared mailbox.

```php
<?php

declare(strict_types=1);

namespace modules\shop;

use craft\elements\User;
use fostercommerce\fostercheckout\events\DefineCheckoutContactEvent;
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
			Checkout::EVENT_DEFINE_CONTACT,
			static function (DefineCheckoutContactEvent $defineContactEvent): void {
				$customer = $defineContactEvent->order->getCustomer();

				if (! $customer instanceof User) {
					return;
				}

				$defineContactEvent->value = "{$customer->fullName} ({$defineContactEvent->order->email})";
			}
		);
	}
}
```

Leave `value` as null and the order's email is used.

## Whether the book is shown

`craft.fostercheckout.canViewAddresses(cart)` answers whether the signed-in user may see the order customer's saved addresses. The checkout lists the book and ships to a picked address only when it is true.

Grant it the way Craft grants any address view: give the signed-in user permission on the customer that owns them.

## Whether addresses can be saved

`craft.fostercheckout.canSaveAddresses(cart)` answers whether the signed-in user may add to or edit the order customer's address book. It asks Craft the same question Craft's own address screens ask, so a permission granted there is honored here.

Use it to gate anything of your own that writes to the book.

```twig
{% if craft.fostercheckout.canSaveAddresses(cart) %}
	{# Your own control that saves an address #}
{% endif %}
```

It is false when nobody is signed in.

## Whether Klaviyo is tracking

`craft.fostercheckout.klaviyoTrackingEnabled(cart)` is false when Klaviyo Connect Plus is not installed, and when the signed-in user's email is not the order's. Guests are tracked, since there is no second person to confuse the events with.

Call it in place of an `isPluginEnabled('klaviyo-connect-plus')` check in your own templates, so a purchasing agent's activity is not attributed to the account holder. See [plugin integrations](../reference/integrations.md#klaviyo-connect-plus).

## Setting the customer

The plugin never sets `order.customer`. Do it when the agent picks the account they are buying for, before the checkout renders.

```php
use Craft;

$order->setCustomer($companyUser);
Craft::$app->getElements()->saveElement($order);
```

Do not do this to a session cart. `Carts::getCart()` reassigns a session cart's customer to the signed-in user whenever their email differs from the cart's, which is exactly this arrangement, so the assignment is undone on the next request. Hold the order outside the session cart and fetch it by number.

With view permission alone the agent picks from the company's addresses but cannot change them. With neither, the checkout offers only the new address form.
