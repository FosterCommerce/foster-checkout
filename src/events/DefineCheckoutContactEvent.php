<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Event for naming the contact the checkout shows in place of the order's email.
 *
 * @since 1.1.0
 */
class DefineCheckoutContactEvent extends Event
{
	public Order $order;

	/**
	 * Null leaves the order's email in place.
	 */
	public ?string $value = null;
}
