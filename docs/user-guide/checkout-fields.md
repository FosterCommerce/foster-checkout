# Checkout fields

How to ask a customer for a detail the checkout does not collect by default.

**Checkout -> Custom Fields** lists five points in the checkout. Each one holds a field layout, and every field you add there is shown to the customer at that point.

| Position | Where it shows |
| --- | --- |
| **Email step** | The contact step, under the email address |
| **Shipping address step** | The shipping address step, under the address |
| **Shipping method step** | The shipping method step |
| **Billing step** | The billing step |
| **Order summary** | The cart page and the order summary |

On the stepped checkout, a signed-in customer skips the email step, so **Email step** fields also show on the first step they reach: the shipping address step, or the billing step when no item ships.

Open a position to edit its field layout, and set each field's width and whether it is required.

## Only fields on the order can be added to a layout

Saving a layout with a field that is not on the order fails, and the message names the handles.

Add the field under **Commerce -> Settings -> Order Fields** first, then come back.

A module or plugin can contribute a field it stores itself, which is not bound by this. See [contributed fields](../dev-guide/contributed-fields.md).

## Supported field types

Plain text, number, dropdown, radio buttons, checkboxes, lightswitch, date and time. Saving a layout with any other type fails, because the checkout has no input to render for it.

## A field can be used in one place only

Saving a position's layout fails when it uses a field already placed in another position, in a gateway's layout at **Checkout -> Gateways**, or contributed by a module. The message names the handles.

## Required fields

Where a required field stops the customer depends on the position.

- **Order summary** blocks the cart. The **Checkout** button is disabled and the missing fields are named above it.
- **Every other position** blocks payment. The customer can move through the checkout and is stopped when they try to pay.

## Placeholders and limits

A field's placeholder and its length limit are set on the field itself under **Commerce -> Settings -> Order Fields**, not in the layout, so they apply everywhere that field is used.
