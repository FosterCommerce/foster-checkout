<?php

namespace fostercommerce\fostercheckout\events;

use craft\commerce\elements\Order;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use yii\base\Event;

/**
 * Carries a checkout position's fields, for a handler to add ones no field layout holds.
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
