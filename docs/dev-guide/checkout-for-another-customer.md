# Checkout for another customer

Run the checkout for an order whose customer is not the signed-in user, such as a purchasing agent who buys for a company account.

No control panel setting turns this on. Your own code sets the order's customer; the checkout reads it.

## What follows the order's customer

When `order.customer` is a user other than the signed-in user, the checkout changes these behaviors:

| What | Behavior |
| --- | --- |
| The address book | Listed from the order's customer, when Craft lets the signed-in user view an address owned by that customer |
| Picking a saved address | Looked up in the order customer's address book, because Commerce looks up a posted ID on the signed-in user |
| A saved address | Owned by the order's customer and saved to that customer's address book |
| Editing and saving addresses | Available only when Craft lets the signed-in user save an address owned by that customer |
| Saved payment methods | Listed from, and saved to, the order's customer |
| Newsletter | The checkout hides the newsletter checkbox, so a purchasing agent cannot subscribe the account holder. See [newsletter services](./newsletter-services.md#when-the-checkbox-is-shown) |

The checkout also names the person signed in, below the contact, whenever their email differs from the order's.

## The contact shown

`craft.fostercheckout.contact(order)` returns what the checkout prints as the contact. By default it returns the order's email. To show a company name or an account reference instead, set `value` in a handler.

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

Leave `value` null to show the order's email.

## Whether the address book is shown

`craft.fostercheckout.canViewAddresses(cart)` returns whether the signed-in user can view the order customer's saved addresses. The checkout lists the address book, and ships to a picked address, only when it returns true.

To let the signed-in user view the addresses, give them Craft's address permission on the customer that owns the addresses.

## Whether addresses can be saved

`craft.fostercheckout.canSaveAddresses(cart)` returns whether the signed-in user can add to or edit the order customer's address book. It runs the same check as Craft's address screens, so the same permissions apply.

Use it to gate your own controls that write to the address book.

```twig
{% if craft.fostercheckout.canSaveAddresses(cart) %}
	{# Your own control that saves an address #}
{% endif %}
```

It returns false when no user is signed in.

If the agent can view the company's addresses but not save them, the checkout lists the addresses but does not let the agent edit them. If the agent can do neither, the checkout shows only the new address form.

## Setting the customer

The plugin does not set `order.customer`. Set it when the agent picks the account they buy for, before the checkout renders.

```php
use Craft;

$order->setCustomer($companyUser);
Craft::$app->getElements()->saveElement($order);
```

Do not set the customer on a session cart. On the next request, `Carts::getCart()` reassigns the session cart's customer to the signed-in user, because their email differs from the cart's. Keep the order outside the session cart and fetch it by number.
