# Checkout fields

How to ask a customer for something the checkout does not collect by default.

**Checkout -> Fields** lists five points in the checkout. Each one holds a field layout, and every field you add there is shown to the customer at that point.

| Position | Where it shows |
| --- | --- |
| Email | The contact step, under the email address |
| Shipping address | The shipping address step, under the address |
| Shipping method | The shipping method step |
| Billing | The billing step |
| Summary | The cart page and the order summary |

Click a position to open its field layout designer. Drag a field in, set its width and whether it is required, then save.

## Only fields on the order can be used

An order stores values through its own field layout. A field that is not on it would render, take what the customer types, and then be discarded with no error. Saving a layout that uses one is rejected, and the message names the handles.

Add the field under **Commerce -> Settings -> Order Fields** first, then come back.

## Supported field types

Plain text, number, dropdown, radio buttons, checkboxes, lightswitch, date and time. Anything else is rejected on save, because the checkout has no input to render for it.

## A handle can only be used once

The same field cannot sit in two positions. Saving a layout that claims a handle another position already uses is rejected, and the message names the position that has it.

## Required fields

Where a required field stops the customer depends on the position.

- **Summary** blocks the cart. The Checkout button is disabled and the missing fields are named above it.
- **Every other position** blocks payment. The customer can move through the checkout and is stopped when they try to pay.

This is worth knowing before you mark a summary field required, because it stops a customer at the cart with no way past it.

## Placeholders and limits

A field's placeholder and its length limit are set on the field itself under **Commerce -> Settings -> Order Fields**, not in the layout, so they apply everywhere that field is used.
