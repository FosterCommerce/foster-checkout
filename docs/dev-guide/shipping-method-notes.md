# Shipping method notes

Show a line of text under each shipping method the checkout lists, such as a transit time or a cutoff, on both layouts. The checkout shows the line under the method's name, after any shipping rule description. To set a note for each method without code, see [notes and links](../user-guide/content.md).

## Answering the event

```php
<?php

declare(strict_types=1);

namespace modules\site;

use fostercommerce\fostercheckout\events\DefineShippingMethodNoteEvent;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

		Event::on(
			Checkout::class,
			Checkout::EVENT_DEFINE_SHIPPING_METHOD_NOTE,
			static function (DefineShippingMethodNoteEvent $defineShippingMethodNoteEvent): void {
				if ($defineShippingMethodNoteEvent->shippingMethodOption->getHandle() === 'pickup') {
					$defineShippingMethodNoteEvent->note = 'Ready the next business day.';
				}
			}
		);
	}
}
```

The event fires once per listed method, with the cart as `order` and the method as `shippingMethodOption`. Its `shippingMethod` property is the method Commerce or a shipping plugin registered. The single-page checkout fires the event again after every save; the stepped checkout fires it when the shipping step renders. `note` starts as the method's note from **Checkout -> Notes & Links**, or null. A handler can add to that note or replace it. To hide the line, set `note` to null. The checkout escapes HTML in the note.

## In Twig

`craft.fostercheckout.shippingMethodNote(cart, method)` returns the note or null, for a custom template that shows the note elsewhere.
