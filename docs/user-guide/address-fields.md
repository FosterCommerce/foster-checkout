# Address fields

What the checkout asks for on a shipping or billing address, how to change it, and how the shipping address is checked.

Two screens decide this together. Craft's address field layout, at **Settings -> Addresses**, sets which fields exist, their order and their width. The checkout renders them in that order. **Checkout -> Addresses** then holds what the checkout does with them.

| Setting | Controls |
| --- | --- |
| **Default country** | Country a new address starts on. The customer can still change it. Unset starts with no country chosen |
| **Priority countries** | Country codes shown at the top of every country dropdown, in the order listed. They are removed from the alphabetical list below |
| **Saved address limit** | How many of a customer’s saved addresses the checkout offers them to ship to. 10 by default |
| **Phone field** | Handle of the address field holding a phone number. Its input asks for a phone keypad instead of a text one |
| **Hidden address fields** | Fields left off the checkout. They stay in the control panel |
| **Hidden billing address fields** | Fields also left off a new billing address, such as a delivery switch, which has no use on the address a card is billed to |
| **Required shipping address fields** | Fields the checkout requires on a shipping address, and on a saved address a customer edits, beyond what the address layout asks for |
| **Required billing address fields** | The same for a new billing address |
| **Show a third address line** | Whether the form offers a third address line. Off by default |
| **Let customers name a saved address** | Whether the label of a saved address is editable at the checkout. Off by default |
| **Show the address label** | Whether a saved address the customer named is shown by that name in the address choices and the completed steps. Off by default |

## Saved addresses

The checkout offers the most recently updated addresses, up to **Saved address limit**, with the primary address first. Set the limit to zero to offer every saved address.

## What can be hidden

The country and the address block itself are always shown, since Craft cannot resolve an address without them. A field the address layout marks required is always shown too, and cannot be un-required at the checkout.

A hidden field is never required, even if it is also ticked in the required list.

The billing lists apply only to the new billing address form. A saved address the customer picks as billing keeps its values, and editing a saved address shows every field, since the address book has no billing or shipping side.

Custom fields you add to the address layout are listed alongside the native ones, so they can be hidden or required in the same way. A custom field only renders where its type has a storefront input: plain text, number, dropdown, radio buttons, checkboxes, lightswitch, date and time.

## The country decides the rest of the form

Each country has an address format naming which fields it uses. Pick France and the second address line stays, the postal code stays, and the state field goes away. The form changes as the customer picks a country.

With **Default country** set, the form opens on that country's format. An address the customer is editing keeps its own country.

The state field is the exception. It shows wherever the country has a list of states, provinces or regions, and its label reads State / Province everywhere.

Craft's format for the United Kingdom has no administrative area, and Craft has no list of UK counties. The plugin adds the administrative area to UK addresses and supplies its own county list, so the state field shows for the United Kingdom too.

The third address line has a switch of its own on top of the format, since nearly every country format lists one. Turn it on and it still shows only where the country's format uses it.

## Address labels

An address has a label, which is what names it in the customer's address book. Commerce sets that label on the addresses attached to an order, so a new address a customer saves during checkout arrives as "Shipping Address" or "Billing Address".

Turn on **Let customers name a saved address** and the label becomes an editable field, but only when a customer edits an address they already saved. It is left off the new address form, because Commerce replaces the label of a new address.

Labels a store sets for its own purposes are never overwritten by the checkout. Leave the switch off to keep customers out of them.

To offer the store location as a shipping address, see [customer pickup](./customer-pickup.md).

## Address verification and suggestions

Two tools help with the shipping address, both set at **Checkout -> Addresses**.

| Setting | Controls |
| --- | --- |
| **Verify shipping addresses** | Whether Avalara checks the shipping address and offers its corrected version, which the customer can accept or decline. Needs the AvaTax plugin with its **Enable address validation** setting on. Off by default |
| **Address suggestions** | Whether matching addresses are listed under the shipping address as the customer types. Picking one fills the form. Off by default |
| **Address suggestion provider** | Google Places or Loqate. Google by default |
| **Address suggestion API key** | The provider's key, set as an environment variable name |
| **Test the connection** | Sends one lookup to the provider and names the error. The checkout does not show lookup failures, so this is the only place a wrong key shows |

Use one tool or the other. Avalara checks an address the customer entered, and suggestions help the customer enter one. Lookup results don't always match what Avalara prefers, so using both can confuse some customers, depending on their address. Only one runs at a time: while verification is running, the checkout skips suggestions.

### Avalara coverage

Avalara covers the United States and Canada. For other countries it answers `Country not supported`, which the checkout treats as no suggestion. A store shipping elsewhere gets no verification and no message saying why. Both suggestion providers work internationally, Loqate the more widely.

### Setting up suggestions

The plugin includes neither provider; the site supplies its own account and key. Set the key as an environment variable so it stays out of project config:

```sh
FC_ADDRESS_LOOKUP_KEY=your-key-here
```

Then set **Address suggestion API key** to `$FC_ADDRESS_LOOKUP_KEY`, not the key itself.

Suggestions appear on the shipping address only, and need **Address suggestions** on and an API key.

The checkout does not show a failed lookup to the customer. A revoked key, an exhausted account or an unreachable provider returns no suggestions and writes the reason to the log.

### Capping the spend

Both providers bill per lookup, and the endpoint that calls them is public. **Cap the spend at the provider.** In Google Cloud, set a daily and a per-minute quota on the Places API. Loqate sells prepaid credit, which caps itself. The plugin sets no rate limit of its own.
