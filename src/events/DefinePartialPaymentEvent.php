<?php

namespace fostercommerce\fostercheckout\events;

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

	public string $appliedLabel = '{amount} applied';
}
