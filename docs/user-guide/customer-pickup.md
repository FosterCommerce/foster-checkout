# Customer pickup

Let a customer collect an order instead of having it shipped. The customer chooses the store location as the shipping address, so a pickup order is a normal order shipped to your own address.

Turn it on with **Enable customer pickup** at **Checkout -> Features**. **Pickup label** sets the text of the pickup choice. When **Pickup label** is blank, the text reads "Customer pickup".

## What the customer sees

With **Pickup display** set to **Address choice**, the default, both checkout layouts list the choice last, after the saved addresses and the new address option, and show the store location beneath it. Choosing it hides the address form. A guest sees the choice too, next to the new address form.

With **Pickup display** set to **Separate options**, both checkout layouts show two options instead. **Ship to an address** lists the saved addresses and the new address form under it, laid out like **Use a different billing address** at the billing step. The pickup choice comes second, with the store location beneath it, and choosing it hides the addresses. Choosing **Ship to an address** again restores the address the customer had chosen before. The address list does not include a pickup choice. A cart already set for pickup opens with the pickup choice selected, and choosing **Ship to an address** then selects the first saved address, or shows the new address form when the customer has no saved address.

A pickup order does not show the **Same as shipping address** billing choice, because the store location is not the customer's billing address. The customer picks or enters a billing address instead.

## Where the address comes from

The choice shows **Store location** at **Commerce -> Store Management -> General**. The pickup choice stays hidden until the store location has a street address.

The order's shipping address is a copy of that location, named for the customer when their name is known and for the pickup label otherwise. Switching back to a saved or new address replaces the copy.

## Pricing pickup

The plugin does not add a shipping method for pickup. To price pickup, create a shipping zone in Commerce for the store location's postal code, and give a free shipping method a rule for that zone. That rule matches every order shipped to the store's postal code.

A rate plugin quotes carriers for the store location like any other address. To leave carrier rates off a pickup order, see [detecting a pickup order](../dev-guide/customer-pickup.md).
