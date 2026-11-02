<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\models\LineItem;
use yii\base\Event;

/**
 * Event for whether the cart page lets the customer change a line item's quantity.
 *
 * @since 2.1.0
 */
class DefineLineItemQtyEditableEvent extends Event
{
	public LineItem $lineItem;

	/**
	 * When false, the cart page shows the quantity as text in place of the stepper.
	 */
	public bool $isQtyEditable = true;
}
