# Address fields

What the checkout asks for on a shipping or billing address, and how to change it.

Two screens decide this together. Craft's address field layout, at **Settings -> Addresses**, sets which fields exist, their order and their width. The checkout renders them in that order. **Checkout -> General** then holds what the checkout does with them.

| Setting | Controls |
| --- | --- |
| **Priority countries** | Country codes shown at the top of every country dropdown, in the order listed. They are removed from the alphabetical list below |
| **Hidden address fields** | Fields left off the checkout. They stay in the control panel |
| **Required address fields** | Fields the checkout requires beyond what the address layout asks for |
| **Show a third address line** | Whether the form offers a third address line. Off by default |
| **Let customers name a saved address** | Whether the label of a saved address is editable at the checkout. Off by default |

## What can be hidden

The country and the address block itself are always shown, since Craft cannot resolve an address without them. A field the address layout marks required is always shown too, and cannot be un-required at the checkout.

A hidden field is never required, even if it is also ticked in the required list.

Custom fields you add to the address layout are listed alongside the native ones, so they can be hidden or required in the same way. A custom field only renders where its type has a storefront input: plain text, number, dropdown, radio buttons, checkboxes, lightswitch, date and time.

## The country decides the rest of the form

Each country has an address format naming which fields it uses. Pick France and the second address line stays, the postal code stays, and the state field goes away. The form changes as the customer picks a country.

The state field is the exception. It shows wherever the country has a list of states, provinces or regions, and its label reads State / Province everywhere.

The United Kingdom has no administrative area in its format. Craft fills the gap with a county list, and the checkout replaces that list with its own.

The third address line has a switch of its own on top of the format, since nearly every country format lists one. Turn it on and it still shows only where the country's format uses it.

## Address labels

An address has a label, which is what names it in the customer's address book. Commerce sets that label on the addresses attached to an order, so a new address a customer saves during checkout arrives as "Shipping Address" or "Billing Address".

Turn on **Let customers name a saved address** and the label becomes an editable field, but only when a customer edits an address they already saved. It is left off the new address form, where anything typed would be replaced by Commerce.

Labels a store sets for its own purposes are never overwritten by the checkout. Leave the switch off to keep customers out of them.

To offer the store location as a shipping address, see [customer pickup](./customer-pickup.md).
