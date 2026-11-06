<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use craft\commerce\base\Gateway;

class PartialPaymentStep extends Model
{
	public Gateway $gateway;

	public float $amount = 0.0;

	public float $applied = 0.0;

	public ?int $accountId = null;

	public ?int $transactionId = null;

	public string $summaryLabel = '';

	public string $label = '{amount}';

	public string $appliedLabel = '{amount}';
}
