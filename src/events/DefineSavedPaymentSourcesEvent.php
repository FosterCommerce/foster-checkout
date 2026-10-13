<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\PaymentSource;
use yii\base\Event;

/**
 * Event for changing which saved payment methods the checkout lists, and whether it shows the save checkbox.
 *
 * @since 2.1.0
 */
class DefineSavedPaymentSourcesEvent extends Event
{
	public Order $order;

	public GatewayInterface $gateway;

	/**
	 * The order customer's saved payment methods for this gateway.
	 *
	 * @var array<int, PaymentSource>
	 */
	public array $paymentSources = [];

	/**
	 * Whether the checkout shows the save checkbox.
	 */
	public bool $canSave = true;
}
