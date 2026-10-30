# Customer pickup

Let a customer collect an order instead of having it shipped. The store location becomes one of the shipping address choices, so a pickup order is a normal order shipped to your own address.

Turn it on with **Enable customer pickup** at **Checkout -> Features**. **Pickup label** sets the choice's name. When **Pickup label** is blank, the choice reads "Customer pickup".

## What the customer sees

Both checkout layouts list the choice last, after the saved addresses and the new address option, and show the store location beneath it. Choosing it hides the address form. A guest sees the choice too, next to the new address form.

A pickup order does not show the **Same as shipping address** billing choice, because the store location is not the customer's billing address. The customer picks or enters a billing address instead.

## Where the address comes from

The choice shows **Store location** at **Commerce -> Store Management -> General**. The pickup choice stays hidden until the store location has a street address.

The order's shipping address is a copy of that location, named for the customer when their name is known and for the pickup label otherwise. Switching back to a saved or new address replaces the copy.

## Pricing pickup

The plugin does not add a shipping method for pickup. To price pickup, create a shipping zone in Commerce for the store location's postal code, and give a free shipping method a rule for that zone. That rule matches every order shipped to the store's postal code.

A rate plugin quotes carriers for the store location like any other address. To leave carrier rates off a pickup order, see [detecting a pickup order](../dev-guide/customer-pickup.md).
