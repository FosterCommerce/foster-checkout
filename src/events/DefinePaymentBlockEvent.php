<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Event for refusing payment at the checkout, with the reason shown in place of the payment form.
 *
 * @since 1.4.1
 */
class DefinePaymentBlockEvent extends Event
{
	public Order $order;

	/**
	 * Null lets the order be paid.
	 */
	public ?string $reason = null;
}
