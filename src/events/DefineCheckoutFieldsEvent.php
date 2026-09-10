<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use yii\base\Event;

/**
 * Checkout fields event.
 *
 * @since 1.1.0
 *
 * @phpstan-import-type RenderableField from CheckoutFieldLayouts
 * @phpstan-import-type RenderableUiElement from CheckoutFieldLayouts
 */
class DefineCheckoutFieldsEvent extends Event
{
	/**
	 * One of CheckoutFieldLayouts::CHECKOUT_POSITIONS.
	 */
	public string $position;

	/**
	 * Null on the settings screen, which lists a position's fields with no order to read.
	 */
	public ?Order $order = null;

	/**
	 * @var array<int, RenderableField|RenderableUiElement>
	 */
	public array $fields = [];
}
