# Contributed fields

A checkout field usually comes from a field layout in **Checkout -> Custom Fields**, and Commerce stores its value on the order. A module or plugin can add a field of its own to the same positions, compute its options per order, and store the value wherever it keeps that data.

Use this where the value is not an order field: a record in your own table, a row keyed by order id, a relationship Craft has no field type for.

## The field shape

Every key is required.

| Key | Value |
| --- | --- |
| `handle` | the name the input posts under, inside `fields[...]` |
| `label` | the input's label |
| `instructions` | help text, or null |
| `value` | the stored value, read back from your own storage. A string, or a list of strings for `checkboxes` |
| `required` | whether the input is marked required. The checkout's scripts check it in every position. On the server, only the cart page checks it, for a `summary` field; payment does not |
| `width` | a percentage, which the grid rounds to twelfths |
| `type` | `text`, `textarea`, `number`, `select`, `radio`, `checkbox`, `checkboxes`, `date`, `datetime-local` or `time` |
| `placeholder`, `maxLength`, `initialRows` | text and textarea inputs only, else null |
| `min`, `max`, `step` | number inputs only, else null |
| `options` | `label` and `value` pairs for `select`, `radio` and `checkboxes`, else an empty array |
| `template` | a template rendered after the input, or null |

`value` is what makes `required` work: read it back from your own storage every time the field is defined, or the checkout treats it as never filled in and the Checkout button stays disabled.

`hr`, `heading` and `linebreak` are UI elements rather than fields. They take `type`, `label` and `width` only, and a `template` on one is never rendered.

## Adding a field

`CheckoutFieldLayouts::EVENT_DEFINE_CHECKOUT_FIELDS` fires whenever a position's fields are listed: on the cart page, through the checkout, on the settings screen, and when a layout is saved. Register the handler from your module's or plugin's `init()`.

```php
<?php

declare(strict_types=1);

namespace modules\projects;

use craft\commerce\elements\Order;
use craft\db\Query;
use fostercommerce\fostercheckout\events\DefineCheckoutFieldsEvent;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

		Event::on(
			CheckoutFieldLayouts::class,
			CheckoutFieldLayouts::EVENT_DEFINE_CHECKOUT_FIELDS,
			function (DefineCheckoutFieldsEvent $event): void {
				if ($event->position !== 'summary') {
					return;
				}

				$order = $event->order;

				$event->fields[] = [
					'handle' => 'projectId',
					'label' => 'Project',
					'instructions' => 'The job this order is for.',
					'value' => $order instanceof Order ? $this->storedProjectId($order) : '',
					'required' => true,
					'width' => 100,
					'type' => 'select',
					'placeholder' => null,
					'maxLength' => null,
					'min' => null,
					'max' => null,
					'step' => null,
					'initialRows' => null,
					'options' => $order instanceof Order ? $this->projectOptions($order) : [],
					'template' => null,
				];
			}
		);
	}

	private function storedProjectId(Order $order): string
	{
		$projectId = (new Query())
			->select(['projectId'])
			->from('{{%order_projects}}')
			->where(['orderId' => $order->id])
			->scalar();

		return $projectId === false ? '' : (string) $projectId;
	}

	/**
	 * @return list<array{label: string, value: string}>
	 */
	private function projectOptions(Order $order): array
	{
		$projects = (new Query())
			->select(['id', 'title'])
			->from('{{%projects}}')
			->where(['customerId' => $order->getCustomerId()])
			->orderBy(['title' => SORT_ASC])
			->all();

		return array_map(
			static fn (array $project): array => [
				'label' => (string) $project['title'],
				'value' => (string) $project['id'],
			],
			$projects,
		);
	}
}
```

| Property | Value |
| --- | --- |
| `position` | `email`, `shippingAddress`, `shippingMethod`, `billing` or `summary` |
| `order` | the order being rendered, or null |
| `fields` | the position's fields so far, in render order. Append to it |

`order` is null on the settings screen and when the plugin collects handles, so contribute the field either way and compute the options only when there is an order. A field at `summary` renders on the cart page and in the checkout summary, and the cart's Checkout button stays disabled while a required one is empty.

The event fires more than once per page, since a position's fields are listed both to render them and to check the required ones. Cache anything expensive on your side. Asking this service for a position's fields from inside the handler returns the layout's fields without firing the event again, so a handler cannot trigger itself.

### Handles

Pick a handle no order field uses. Both post under `fields`, and the order's own field would take the value.

Contributed handles are collected the same way a layout's are, so saving a checkout layout that uses one is rejected with an error naming it. The field layout designer still offers the field; saving rejects it. Gateway layouts are not checked.

## Storing the value

`CheckoutFieldLayouts::EVENT_APPLY_CHECKOUT_FIELDS` fires from the cart's `beforeValidate` during a `commerce/cart/update-cart` request, with the cart and everything posted under `fields`.

```php
<?php

declare(strict_types=1);

namespace modules\projects;

use craft\commerce\elements\Order;
use craft\db\Query;
use craft\helpers\Db;
use fostercommerce\fostercheckout\events\ApplyCheckoutFieldsEvent;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use yii\base\Event;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	public function init(): void
	{
		parent::init();

		Event::on(
			CheckoutFieldLayouts::class,
			CheckoutFieldLayouts::EVENT_APPLY_CHECKOUT_FIELDS,
			function (ApplyCheckoutFieldsEvent $event): void {
				if (! array_key_exists('projectId', $event->values)) {
					return;
				}

				if (! $this->storeProjectId($event->order, $event->values['projectId'])) {
					$event->order->addError('projectId', 'That project is no longer available.');
					$event->isValid = false;
				}
			}
		);
	}

	private function storeProjectId(Order $order, mixed $projectId): bool
	{
		if (! is_string($projectId)) {
			return false;
		}

		$isOffered = (new Query())
			->from('{{%projects}}')
			->where([
				'id' => $projectId,
				'customerId' => $order->getCustomerId(),
			])
			->exists();

		if (! $isOffered) {
			return false;
		}

		Db::upsert('{{%order_projects}}', [
			'orderId' => $order->id,
			'projectId' => $projectId,
		], updateTimestamp: false);

		return true;
	}
}
```

`values` holds the whole `fields` payload, so check for your own handle before reading it. The value is whatever the browser posted: validate it, and set `$event->isValid = false` to refuse the update rather than storing an id you did not offer. The cart update then fails, and the errors added to the order are reported the way Commerce reports its own. The checkout renders `order.getErrors('yourHandle')` under the input.

It fires once per request, after the posted data has been applied to the cart and before the cart is written, so the order is in the state the update leaves it in and a change made here is part of the same save. No other request fires it.

It is not a validation hook for payment. A field that must be filled in before paying needs its own check on `Payments::EVENT_BEFORE_PROCESS_PAYMENT`, since a customer can reach payment without passing through the cart page again.

The event fires from the cart's own validation, before the save begins, so a write made here is not rolled back if the save fails afterward.

## Adding your own markup

Set `template` to a template path and it renders directly after the input. `template` adds markup after the input; it does not replace it.

```php
'template' => '_checkout/new-project',
```

Use it for anything an input cannot express: a link to the record, a hint drawn from your own data, a dialog for creating an option without leaving the checkout.

The template is included without `only`, so it receives `field` and `cart` along with everything else in the surrounding scope. Only `field` and `cart` are a contract.

The cart page and the multi-step checkout render these fields inside a form, and HTML has no nested form. Put a form of your own inside a `<template>` element, whose contents the parser keeps intact, and move it to the body, outside the form.

```twig
<template data-my-dialog>
	<dialog id="myDialog">
		<form method="post">{# ... #}</form>
	</dialog>
</template>

{% js at endBody %}
	for (const template of document.querySelectorAll('template[data-my-dialog]')) {
		document.body.append(template.content);
	}
{% endjs %}
```

The cart and checkout run on the plugin's own layout, so a template of yours only has what that layout loads. Site CSS, theme variables and site JavaScript are not there.
