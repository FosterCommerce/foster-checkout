# Order summary rows

Build a custom order summary, such as an email or PDF, whose rows match the checkout's and add up to the total.

```twig
{{ craft.fostercheckout.itemsTotal(order) }}
{% for row in craft.fostercheckout.orderDiscounts(order) %}{{ row.name }} {{ row.amountAsCurrency }}{% endfor %}
```

`itemsTotal()` returns a formatted string. The other three helpers return a list of rows, each with a `name` and an `amountAsCurrency`. Each helper takes an order or a cart. See Commerce's [orders and carts](https://craftcms.com/docs/commerce/5.x/system/orders-carts.html) docs for adjustments.

## Example

1. Print the Subtotal with `itemsTotal()`.
2. Print Shipping from the order.
3. Loop over `orderDiscounts()` and `otherAdjustments()`.
4. Print Tax from the order.
5. Loop over `afterTaxDiscounts()`.
6. Print the Total from the order.

```twig
<dl>
	<dt>Subtotal</dt>
	<dd>{{ craft.fostercheckout.itemsTotal(order) }}</dd>

	<dt>Shipping</dt>
	<dd>{{ order.totalShippingCostAsCurrency }}</dd>

	{% for row in craft.fostercheckout.orderDiscounts(order) %}
		<dt>{{ row.name }}</dt>
		<dd>{{ row.amountAsCurrency }}</dd>
	{% endfor %}

	{% for row in craft.fostercheckout.otherAdjustments(order) %}
		<dt>{{ row.name }}</dt>
		<dd>{{ row.amountAsCurrency }}</dd>
	{% endfor %}

	<dt>Tax</dt>
	<dd>{{ order.totalTaxAsCurrency }}</dd>

	{% for row in craft.fostercheckout.afterTaxDiscounts(order) %}
		<dt>{{ row.name }}</dt>
		<dd>{{ row.amountAsCurrency }}</dd>
	{% endfor %}

	<dt>Total</dt>
	<dd>{{ order.totalPriceAsCurrency }}</dd>
</dl>
```

Keep this order, which matches the checkout's summary. Labels are yours to change.

Gift Voucher's `voucher` adjustments have no row helper. To add them, loop over `order.adjustments` before the Total, and keep the ones whose `type` is `voucher`. `craft.fostercheckout.voucherLabel(adjustment)` returns the label the checkout shows.

The checkout also lists [partial payments](./partial-payments.md) and subtracts them from its Total. This example leaves them out.

## Subtotal

`itemsTotal()` returns the items after line-level discounts. Commerce stores its own discounts on line items, including a flat amount off the order. Those discounts have no row, and a Subtotal before them makes the rows add up to more than the total.

| Value | Line discounts | Other line adjustments | Returns |
|---|---|---|---|
| `order.itemSubtotal` | Not included | Not included | Number |
| `order.itemTotal` | Included | Included | Number |
| `craft.fostercheckout.itemsTotal(order)` | Included | Not included | Formatted string |

`order.itemTotal` also counts line-level shipping, tax, and fees, and those amounts show again in their own rows.

## Helpers

### `itemsTotal(order)`

The Subtotal row. To calculate with the Subtotal, add each line item's `discount` to `order.itemSubtotal`.

### `orderDiscounts(order)`

Order-level discounts stored before the order's first tax adjustment, or all of them when the order has no tax adjustment. Commerce's own discounts are not in this list, because Commerce stores them on line items.

### `otherAdjustments(order)`

Adjustments that aren't shipping, tax, a discount, or a voucher, such as a fee from another plugin. `otherAdjustments()` leaves out adjustments already included in a price.

### `afterTaxDiscounts(order)`

Order-level discounts stored after the order's first tax adjustment.

## Edge cases

- Rows with the same name merge into one. One order-level discount covering both items and shipping shows one combined amount.
- With no matching adjustments, the three row helpers return an empty list.
- If discounts exceed the order value and the store's minimum total price strategy is **Zero** or **Shipping**, the rows add up to less than the total.
