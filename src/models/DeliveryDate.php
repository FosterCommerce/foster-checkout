<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use DateTime;

class DeliveryDate extends Model
{
	/**
	 * Label for the delivery date.
	 */
	public ?string $label = null;

	/**
	 * Message to display regarding delivery date.
	 */
	public ?string $message = null;

	/**
	 * Value for the delivery date.
	 */
	public ?DateTime $estimate = null;
}
