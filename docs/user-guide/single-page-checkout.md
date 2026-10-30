# Single-page checkout

The checkout runs either as separate steps or as one page. **Multi-page** is the default, for backward compatibility.

To use one page, set **Checkout layout** to **Single-page** at **Checkout -> Appearance**.

## What changes

The customer sees four panels on one page, with the order summary beside them: **Contact information**, **Shipping address**, **Shipping method** and **Payment**. The billing address is part of **Payment**. On an order totaling zero, **Payment** leaves out the billing address unless **Require Billing Address At Checkout** is on at **Commerce -> Settings -> Stores**. When no item ships, the **Shipping method** panel is absent, and so is **Shipping address** unless **Require Shipping Address At Checkout** is on at **Commerce -> Settings -> Stores**. The page does not submit a form between panels. The cart saves in the background each time the customer leaves a field or changes a choice, and each panel shows its own **Saving** and **Saved** state.

The URL stays the same throughout. While the single-page layout is selected, the stepped URLs `/email`, `/address`, `/shipping`, `/billing` and `/payment` under the checkout path redirect to the single page, so a bookmarked link still works. The order confirmation page at `/order` does not redirect.

## Choosing a layout

The stepped layout suits a checkout with many [checkout fields](./checkout-fields.md). It sends fewer update-cart requests than the single page.

## What stays the same

These settings apply to both layouts: checkout copy, checkout fields, payment gateways and the names customers read for them, line item display, [address fields](./address-fields.md), address verification and address suggestions. You can switch layouts without changing these settings.

## Behaviors that apply only to the single page

**Shipping rates refresh each time the address saves.** On the stepped checkout, the customer submits the address once, so the checkout fetches rates once. On the single page, the checkout fetches rates each time the customer leaves an address field.

**The phone number is not required until payment.** If the address layout requires the `phone` field, the background saves accept an address without it, so a partly entered address does not fail validation. The checkout still requires the phone number before payment.

**A checkout field saves before its panel is complete.** While a panel has an unfilled required field, a checkout field in that panel saves on its own when the customer leaves it. The rest of the panel saves once it is complete.

## Payment forms

With more than one gateway available, the checkout does not select a gateway until the customer chooses one. Stripe's form loads only for the selected Stripe gateway.

Stripe and PayPal build their forms for the order total when the form loads. Stripe's form reloads when the total changes or when the billing address it pre-fills changes. Editing an address after opening the payment panel reloads the form only when one of those values changes. PayPal's form reloads whenever the cart changes. To see when a Mollie gateway's list of payment methods updates, see [Mollie](../reference/integrations.md#mollie).
