<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use Money\Money;

class OrderReceipt extends Model
{
	/**
	 * @var list<ReceiptPayment>
	 */
	public array $payments = [];

	/**
	 * @var list<ReceiptPayment>
	 */
	public array $partialPayments = [];

	public Money $customerTotal;

	public Money $paid;

	public Money $balance;

	/**
	 * Nothing paid remains and refunds reach the order total, so the order counts as settled.
	 */
	public bool $refundedInFull = false;
}
