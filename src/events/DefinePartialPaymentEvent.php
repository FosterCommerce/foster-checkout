<?php

namespace fostercommerce\fostercheckout\events;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use yii\base\Event;

class DefinePartialPaymentEvent extends Event
{
	public Order $order;

	public GatewayInterface $gateway;

	public ?float $amount = null;

	public ?string $summaryLabel = null;

	public string $label = '{amount}';

	public string $appliedLabel;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		$this->appliedLabel = Craft::t('foster-checkout', 'partialPayment.applied');
		parent::__construct($config);
	}
}
