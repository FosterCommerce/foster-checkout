<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use craft\commerce\elements\Order;

/**
 * Model for Delivery Date configuration.
 */
class DeliveryDateConfig extends Model
{
	/**
	 * @var string|null Label for the delivery date.
	 */
	public ?string $label = 'Expected delivery date';

	/**
	 * @var string|null Message to display regarding delivery date.
	 */
	public ?string $message = null;

	/**
	 * @var callable(Order): ?string|string|null Value for the delivery date. A string will be processed as a Twig template.
	 */
	public mixed $value = null;

	/**
	 * @var callable(Order): bool|string|bool Whether to display the delivery date. A string will be processed as a Twig template.
	 */
	public mixed $display = false;
}
