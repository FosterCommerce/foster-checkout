# Single-page checkout

The checkout runs either as separate steps or as one page. Multi-page is the default.

Turn it on at **Checkout -> Features -> Checkout layout**.

## What changes

The customer sees four panels on one page, with the order summary beside them: contact, delivery, shipping method and payment. Nothing is submitted between panels. The cart saves in the background as they type, and each panel shows its own saving and saved state.

The URL stays the same throughout. The stepped URLs (`/checkout/email`, `/checkout/address` and the rest) redirect to the single page while this is on, so a bookmarked link still works.

## What stays the same

Every setting outside this one applies to both layouts: checkout copy, checkout fields, payment gateways, line item display, address verification and address suggestions. A store can switch between layouts without reconfiguring any of it.

## Two behaviors that only apply here

**Shipping rates refresh as the address is typed.** On the stepped checkout the address is submitted once, so rates are fetched once. On one page the address changes under the shipping panel, so rates refetch as it settles.

**The phone number is not required until payment.** The cart saves continuously, and a half-typed address would otherwise fail validation on every keystroke. The field is still required before an order can be placed.

## Payment forms

Stripe and PayPal mount their own forms and hold the order total at the moment they mount. Because the total can change while the customer is still on the page, those forms are rebuilt whenever the cart changes. A customer who edits their address after opening the payment panel sees the form reload, which is expected.
