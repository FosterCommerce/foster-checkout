<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use craft\elements\Address;
use yii\base\Event;

/**
 * Event for changing which saved addresses the checkout lists for shipping.
 *
 * @since 2.1.0
 */
class DefineShippingAddressesEvent extends Event
{
	public Order $order;

	/**
	 * The order customer's saved addresses.
	 *
	 * @var array<int, Address>
	 */
	public array $addresses = [];
}
