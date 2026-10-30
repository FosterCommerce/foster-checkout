# Saved payment methods

With **Enable saved payment methods** on, a signed-in customer paying through a Stripe gateway can pay with a saved payment method, or save a new one. Turn it on at **Checkout -> Features**. It is off by default.

## What the customer sees

At payment, the checkout lists the saved payment methods of the order's customer for the selected Stripe gateway, then **Use a new payment method**. Each one shows its description, plus its brand and last four digits when the description does not already end with the last four.

The checkout preselects the payment method already on the cart. Without one, it preselects the customer's primary payment method, and without that, the first in the list. With a saved payment method selected, the customer sees a pay button and no Stripe form. With **Use a new payment method** selected, or with no saved payment methods, the customer sees Stripe's form and a **Save this payment method for future purchases** checkbox.

A guest sees only Stripe's form, and so does every customer while **Enable saved payment methods** is off. Other gateways do not list saved payment methods.

## List or dropdown

**Saved payment method display** at **Checkout -> Features** sets how the checkout lists saved payment methods. Default: **Same as saved addresses**.

| Option | Shows |
| --- | --- |
| **Same as saved addresses** | The **Saved address display** and **Dropdown threshold** settings at **Checkout -> Addresses**. See [saved addresses](./address-fields.md#list-or-dropdown) |
| **List** | A row for each saved payment method |
| **Dropdown** | One searchable dropdown |

## Saving a payment method

When the customer checks the box, Stripe saves the payment method to the order's customer. Commerce lists it once Stripe sends the `payment_method.attached` webhook. Without the gateway's webhook, Commerce does not record the payment method, and the checkout does not list it.

To check out on behalf of a company account, see [checkout for another customer](../dev-guide/checkout-for-another-customer.md).

To change which saved payment methods the checkout lists for a user, or hide the save checkbox, see [saved payment methods per user](../dev-guide/saved-payment-sources.md).
