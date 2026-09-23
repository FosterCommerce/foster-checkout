# Single-page checkout

The checkout runs either as separate steps or as one page. Multi-page is the default, for backward compatibility.

Turn it on at **Checkout -> Appearance -> Checkout layout**.

## What changes

The customer sees four panels on one page, with the order summary beside them: **Contact information**, **Shipping address**, **Shipping method** and **Payment**. The billing address is part of **Payment**. When no item ships, the **Shipping method** panel is absent, and so is **Shipping address** unless the store requires one. The page does not submit a form between panels. The cart saves in the background as they type, and each panel shows its own saving and saved state.

The URL stays the same throughout. While this is on, the stepped URLs `/email`, `/address`, `/shipping`, `/billing` and `/payment` under the checkout path redirect to the single page, so a bookmarked link still works. The order confirmation page at `/order` does not redirect.

## Choosing a layout

The stepped layout suits a checkout with many [checkout fields](./checkout-fields.md). It sends fewer update-cart requests than the single page, which saves as the customer types.

## What stays the same

These settings apply to both layouts: checkout copy, checkout fields, payment gateways and the names customers read for them, line item display, [address fields](./address-fields.md), address verification and address suggestions. A store can switch between layouts without reconfiguring them.

## Two behaviors that only apply here

**Shipping rates refresh as the address is typed.** On the stepped checkout the address is submitted once, so rates are fetched once. On one page, rates refetch as the address changes.

**The phone number is not required until payment.** If the address layout requires the `phone` field, the background saves accept an address without it, so a half-typed address does not fail validation. The field is still required before an order can be placed.

## Payment forms

Stripe and PayPal mount their own forms against the order total at the moment they mount. Stripe's form is rebuilt when the total changes, or when the billing address it pre-fills changes, so editing an address after opening the payment panel reloads it only when one of those changes. PayPal's form is rebuilt whenever the cart changes.
