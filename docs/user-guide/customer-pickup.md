# Customer pickup

Let a customer collect an order instead of having it shipped. The store location becomes one of the shipping address choices, so a pickup order is a normal order shipped to your own address.

Turn it on at **Checkout -> General -> Offer pickup at the store location**. The choice is named by **Pickup label**, which shows “Customer pickup” when blank.

## What the customer sees

Both checkout layouts list the choice after the saved addresses and the new address form, showing the store location beneath the label. Choosing it hides the address form. A guest sees the choice too, next to the new address form.

The “same as shipping address” billing choice is not offered on a pickup order, since the store location is nobody's billing address. The customer picks or enters a billing address instead.

## Where the address comes from

The choice shows the address at **Commerce -> Store Management -> General -> Store location**. It stays hidden until that address has a street.

The order's shipping address is a copy of that location, named for the customer when their name is known and for the pickup label otherwise. Switching back to a saved or new address replaces the copy.

## Pricing pickup

Pickup is priced like anything else: with a shipping method. Make a shipping zone matching the store location's postal code and give a free shipping method a rule for that zone, so it matches orders shipped to the store's postal code.

A rate plugin quotes carriers for the store location like any other address. Leaving those off a pickup order takes a little code; see [detecting a pickup order](../dev-guide/customer-pickup.md).
