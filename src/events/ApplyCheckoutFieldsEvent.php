<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Checkout field values event.
 *
 * @since 1.1.0
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
	 * Set false to refuse the cart update. Add errors to the order to say why.
	 */
	public bool $isValid = true;
}
