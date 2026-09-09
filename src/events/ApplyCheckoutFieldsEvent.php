<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Hands a cart update's posted values to whoever contributed the fields.
 *
 * A contributed field is stored by its contributor, since the order layout has no field for it.
 */
class ApplyCheckoutFieldsEvent extends Event
{
	public Order $order;

	/**
	 * Everything posted under `fields`, keyed by handle.
	 *
	 * @var array<string, mixed>
	 */
	public array $values = [];

	/**
	 * Set false to refuse the cart update, which reports the errors added to the order.
	 */
	public bool $isValid = true;
}
