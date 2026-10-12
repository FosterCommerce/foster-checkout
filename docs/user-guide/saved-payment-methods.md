# Saved payment methods

With **Enable saved payment methods** on, a signed-in customer paying through a Stripe gateway can pay with a saved payment method, or save a new one. Turn it on at **Checkout -> Features**. It is off by default.

## What the customer sees

At payment, the checkout lists the order customer's saved payment methods for the selected Stripe gateway, then **Use a new payment method**. Each one shows its description, plus its brand and last four digits when the description does not already end with the last four.

The checkout preselects the payment method already on the cart, then the customer's primary one, then the first in the list. With a saved payment method selected, the customer sees a pay button and no Stripe form. With **Use a new payment method** selected, or with no saved payment methods, the customer sees Stripe's form and a **Save this payment method for future purchases** checkbox.

A guest sees only Stripe's form, and so does every customer while **Enable saved payment methods** is off. Other gateways do not list saved payment methods.

## List or dropdown

**Saved payment method display** at **Checkout -> Features** sets how the checkout lists them. Default: **Same as saved addresses**.

| Option | Shows |
| --- | --- |
| **Same as saved addresses** | What **Saved address display** and **Dropdown threshold** at **Checkout -> Addresses** choose. See [saved addresses](./address-fields.md#list-or-dropdown) |
| **List** | A row for each saved payment method |
| **Dropdown** | One searchable dropdown |

## Saving a payment method

When the customer ticks the checkbox, Stripe saves the payment method to the order's customer. Commerce lists it once Stripe sends the `payment_method.attached` webhook. Without the gateway's webhook, Commerce does not record the payment method, and the checkout does not list it.

To check out on behalf of a company account, see [checkout for another customer](../dev-guide/checkout-for-another-customer.md).
