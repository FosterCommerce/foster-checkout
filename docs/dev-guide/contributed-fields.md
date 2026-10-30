# Contributed fields

Most checkout fields come from the field layouts at **Checkout -> Custom Fields**, and Commerce stores their values on the order. A module or plugin can also add its own field to the same positions, compute its options per order, and store its value in its own storage.

Contribute a field when its value is not an order field: a record in your own table, a row keyed by order ID, or a relationship Craft has no field type for.

## The field shape

Every key is required.

| Key | Value |
| --- | --- |
| `handle` | The name the input posts under, inside `fields[...]` |
| `label` | The input's label |
| `instructions` | Help text, or null |
| `value` | The stored value, read back from your own storage. A string, or a list of strings for `checkboxes` |
| `required` | Whether the input is marked required. The checkout's scripts check it in every position. On the server, only the cart page checks it, and only for a `summary` field. The payment request does not check it |
| `width` | A percentage, which the grid rounds to twelfths |
| `type` | `text`, `textarea`, `number`, `select`, `radio`, `checkbox`, `checkboxes`, `date`, `datetime-local` or `time` |
| `placeholder`, `maxLength`, `initialRows` | Text and textarea inputs only, else null |
| `min`, `max`, `step` | Number inputs only, else null |
| `options` | `label` and `value` pairs for `select`, `radio` and `checkboxes`, else an empty array |
| `template` | A template rendered after the input, or null |

Read `value` back from your own storage every time your handler runs. Without the stored value, the checkout treats a required field as empty and keeps the **Checkout** button disabled.

`hr`, `heading` and `linebreak` are UI elements rather than fields. They take `type`, `label` and `width` only, and the checkout does not render a `template` set on one.

## Adding a field

`CheckoutFieldLayouts::EVENT_DEFINE_CHECKOUT_FIELDS` fires whenever the plugin lists a position's fields: on the cart page, on each checkout page, on the settings screen, and when you save a layout. Register the handler from your module's or plugin's `init()`.

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
			function (DefineCheckoutFieldsEvent $defineCheckoutFieldsEvent): void {
				if ($defineCheckoutFieldsEvent->position !== 'summary') {
					return;
				}

				$order = $defineCheckoutFieldsEvent->order;

				$defineCheckoutFieldsEvent->fields[] = [
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
| `order` | The order being rendered, or null |
| `fields` | The position's fields so far, in render order. Append to it |

`order` is null on the settings screen and when the plugin collects handles. Add the field in both cases, and compute its options only when `order` is set. A field at `summary` renders on the cart page and in the checkout summary, and the cart's **Checkout** button stays disabled while a required field is empty.

The event fires more than once per page, because the plugin lists a position's fields both to render them and to check the required ones. Cache expensive queries in your handler. Inside the handler, asking `CheckoutFieldLayouts` for a position's fields returns the layout's fields without firing the event again, so a handler cannot trigger itself.

### Handles

Pick a handle that no order field uses. Contributed fields and order fields both post under `fields`, and on a clash the order field takes the value.

The plugin collects contributed handles the same way it collects a layout's handles. The field layout designer still lists a contributed field, but saving a checkout layout that uses its handle fails with an error naming the handle. The plugin does not check gateway layouts.

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
			function (ApplyCheckoutFieldsEvent $applyCheckoutFieldsEvent): void {
				if (! array_key_exists('projectId', $applyCheckoutFieldsEvent->values)) {
					return;
				}

				if (! $this->storeProjectId($applyCheckoutFieldsEvent->order, $applyCheckoutFieldsEvent->values['projectId'])) {
					$applyCheckoutFieldsEvent->order->addError('projectId', 'That project is no longer available.');
					$applyCheckoutFieldsEvent->isValid = false;
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

`values` holds the whole `fields` payload. Check that your handle is present before reading its value. Validate the posted value. To refuse the update, set `$applyCheckoutFieldsEvent->isValid = false` instead of storing an ID your input did not list. The cart update then fails, and Commerce reports the errors you added to the order the same way it reports its own. The checkout renders `order.getErrors('yourHandle')` under the input.

The event fires once per request, after Commerce applies the posted data to the cart and before Commerce saves the cart. The order is in its updated state, and a change your handler makes is part of the same save. Only `commerce/cart/update-cart` requests fire the event.

The event does not validate payment. To require a field before payment, check its value on `Payments::EVENT_BEFORE_PROCESS_PAYMENT`, because a customer can pay without returning to the cart page.

A failed save does not roll back a write your handler makes, because the event fires during the cart's validation, before the save begins.

## Adding your own markup

Set `template` to a template path. The checkout renders that template directly after the input, without replacing the input.

```php
'template' => '_checkout/new-project',
```

Use the template for markup an input cannot provide: a link to the record, a hint drawn from your own data, a dialog for creating an option without leaving the checkout.

The checkout includes the template without `only`, so the template receives `field` and `cart` along with every other variable in the surrounding scope. Only `field` and `cart` are a contract.

The cart page and the stepped checkout render these fields inside a form, and HTML does not allow a form inside another form. Put your own form inside a `<template>` element, whose contents the browser keeps intact, and move those contents to the body, outside the checkout form.

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

The cart and checkout use the plugin's own layout, so your template gets only the CSS and JavaScript that layout loads. The layout does not load your site's CSS, theme variables or JavaScript.
