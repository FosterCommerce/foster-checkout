# Saved addresses for shipping

Remove saved addresses from the shipping step for an order, such as a PO box when your carriers don't deliver to one. The checkout lists the order customer's saved addresses, then fires this event with the list before rendering the shipping step. The billing list keeps every address.

## Answering the event

```php
<?php

declare(strict_types=1);

namespace modules\site;

use craft\elements\Address;
use fostercommerce\fostercheckout\events\DefineShippingAddressesEvent;
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
			Checkout::EVENT_DEFINE_SHIPPING_ADDRESSES,
			static function (DefineShippingAddressesEvent $defineShippingAddressesEvent): void {
				$defineShippingAddressesEvent->addresses = array_filter(
					$defineShippingAddressesEvent->addresses,
					static fn (Address $address): bool => $address->countryCode === 'US',
				);
			}
		);
	}
}
```

`addresses` is the list of the order customer's saved addresses. `order` is the cart.

## What it does not do

The event changes only what the shipping step lists. A customer can still type the same address as a new one, and a request can still post a removed address's ID. Refuse those in your module with a rule on `craft\commerce\elements\Order::EVENT_DEFINE_RULES`.

## In Twig

`craft.fostercheckout.shippingAddresses(cart)` returns the list after any handler has run, for a custom shipping template.

## Saved addresses missing a required field

A saved address can be missing a field after you require it at **Checkout -> Addresses**. The checkout still lists the address. When a customer selects it, the checkout opens its edit form and refuses payment until the field is filled in. A customer who can't edit saved addresses sees a notice to choose another address or enter a new one.

`craft.fostercheckout.incompleteAddressIds(addresses)` returns the IDs of those addresses, for a custom template. Pass `true` as the second argument to check them against the billing address's required fields.
