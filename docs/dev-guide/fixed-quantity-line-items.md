# Fixed quantity line items

Show a line item's quantity on the cart page as text, in place of the quantity stepper, such as for a ticket with one attendee's name. The cart page shows the **Remove** button at every screen width.

## Answering the event

```php
<?php

declare(strict_types=1);

namespace modules\site;

use craft\commerce\enums\LineItemType;
use fostercommerce\fostercheckout\events\DefineLineItemQtyEditableEvent;
use fostercommerce\fostercheckout\services\Checkout;
use verbb\events\elements\Ticket;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

		Event::on(
			Checkout::class,
			Checkout::EVENT_DEFINE_LINE_ITEM_QTY_EDITABLE,
			static function (DefineLineItemQtyEditableEvent $defineLineItemQtyEditableEvent): void {
				$lineItem = $defineLineItemQtyEditableEvent->lineItem;
				if ($lineItem->type !== LineItemType::Custom && $lineItem->getPurchasable() instanceof Ticket) {
					$defineLineItemQtyEditableEvent->isQtyEditable = false;
				}
			}
		);
	}
}
```

The event fires once per line item each time the plugin's cart template renders. `isQtyEditable` starts as true. `getPurchasable()` throws on a custom line item, so check the line item's type first.

## What it does not do

The event changes only the cart page. Commerce still saves a quantity that a request posts to `commerce/cart/update-cart`. To refuse that quantity, add a rule in your module with `craft\commerce\models\LineItem::EVENT_DEFINE_RULES`.

## In Twig

`craft.fostercheckout.isLineItemQtyEditable(lineItem)` returns `true` or `false` after any handler has run, for a custom cart template.
