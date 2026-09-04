<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use DateTime;

class DeliveryDate extends Model
{
	public ?string $label = null;

	public ?string $message = null;

	public ?DateTime $estimate = null;
}
