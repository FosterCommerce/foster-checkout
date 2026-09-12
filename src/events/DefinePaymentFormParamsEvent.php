<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use yii\base\Event;

/**
 * Event for changing the parameters a gateway's payment form is rendered with, per order.
 *
 * @since 1.4.2
 */
class DefinePaymentFormParamsEvent extends Event
{
	public Order $order;

	public GatewayInterface $gateway;

	/**
	 * What the checkout passes to the gateway's `getPaymentFormHtml()`.
	 *
	 * @var array<string, mixed>
	 */
	public array $params = [];
}
