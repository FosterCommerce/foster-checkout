<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use DateTime;
use Money\Money;

class ReceiptPayment extends Model
{
	public int $transactionId;

	public string $label;

	public ?string $cardBrand = null;

	public ?string $cardLast4 = null;

	public Money $amount;

	public Money $refunded;

	public Money $coverage;

	public ?DateTime $date = null;

	public bool $authorized = false;

	public bool $partial = false;

	public bool $applied = false;
}
