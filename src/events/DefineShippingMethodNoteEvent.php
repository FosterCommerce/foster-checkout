<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use craft\commerce\models\ShippingMethodOption;
use yii\base\Event;

/**
 * Event for a line of text under a shipping method at the checkout, such as its transit time.
 *
 * @since 2.1.0
 */
class DefineShippingMethodNoteEvent extends Event
{
	public Order $order;

	/**
	 * `shippingMethod` is the provider's own method.
	 */
	public ShippingMethodOption $shippingMethodOption;

	/**
	 * Starts as the method's note from Notes & Links, or null. A null note is not shown.
	 */
	public ?string $note = null;
}
