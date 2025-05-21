<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class DeliveryDate extends Model
{
	/**
	 * @var string|null Label for the delivery date.
	 */
	public ?string $label = null;

	/**
	 * @var string|null Message to display regarding delivery date.
	 */
	public ?string $message = null;

	/**
	 * @var string|null Value for the delivery date.
	 */
	public ?string $value = null;
}
