# Checkout fields

How to ask a customer for a detail the checkout does not collect by default.

**Checkout -> Custom Fields** lists five positions in the checkout. Each position has a field layout, and the checkout shows every field you add to it at that position.

| Position | Where it shows |
| --- | --- |
| **Email step** | The contact step, under the email address |
| **Shipping address step** | The shipping address step, under the address |
| **Shipping method step** | The shipping method step |
| **Billing step** | The billing step |
| **Order summary** | The cart page and the order summary |

On the stepped checkout, a signed-in customer skips the email step unless the checkout shows the newsletter checkbox. When the checkout skips the email step, **Email step** fields show on the first step the customer sees: the shipping address step, or the billing step when the checkout does not ask for a shipping address.

Open a position to edit its field layout, and set each field's width and whether it is required.

## Only fields on the order can be added to a layout

Saving a layout with a field that is not on the order fails, and the error message names the field handles.

Add the field under **Commerce -> Settings -> Order Fields** first.

This rule does not apply to a field that a module or plugin contributes and stores itself. For those fields, see [contributed fields](../dev-guide/contributed-fields.md).

## Supported field types

Plain text, number, dropdown, radio buttons, checkboxes, lightswitch, date and time. Saving a layout with any other type fails, because the checkout has no input to render for it.

## A field can be used in one place only

Saving a position's layout fails when it uses a field already placed in another position, in a gateway's layout at **Checkout -> Gateways**, or contributed by a module. The error message names the field handles.

## Required fields

Where a required field stops the customer depends on the position.

- **Order summary** blocks the cart. The cart disables the **Checkout** button and names the missing fields above it. The list of missing fields and the button update as each field saves.
- **Every other position** blocks payment. The customer can move through the checkout and the checkout stops them at payment.

## Order notes

**Customer order notes field** at **Checkout -> Custom Fields** names the order field that stores a customer's note. Leave it blank to hide the order notes form.

## When fields save

On the cart page and in the order summary on each checkout step, **Order summary** fields and the order note save when the customer leaves a field or changes a choice. Each form shows **Saving**, then **Saved**, and has no **Save** button. On the single-page checkout, the order note saves when the customer leaves it, and **Save note** appears only after a save fails.

## Placeholders and limits

Set a field's placeholder and length limit on the field itself under **Commerce -> Settings -> Order Fields**, not in the layout. They apply wherever the field appears.
