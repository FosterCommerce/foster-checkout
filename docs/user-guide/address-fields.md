# Address fields

What the checkout asks for on a shipping or billing address, how to change it, and how the checkout checks the shipping address.

Two screens decide this together. Craft's address field layout, at **Settings -> Addresses**, sets which fields exist, their order and their width. The checkout renders them in that order. **Checkout -> Addresses** then sets what the checkout does with them.

| Setting | Controls |
| --- | --- |
| **Default country** | Country a new address starts on. The customer can still change it. Unset starts with no country chosen |
| **Priority countries** | Country codes shown at the top of every country dropdown, in the order listed. The dropdown leaves them out of the alphabetical list below |
| **Saved address limit** | How many of a customer's saved addresses the checkout lists. 10 by default |
| **Saved address display** | Whether the checkout shows saved addresses as a list, a searchable dropdown, or a list until there are more than **Dropdown threshold**. List by default |
| **Dropdown threshold** | The most saved addresses **Auto** still shows as a list. 5 by default |
| **Phone field** | Handle of the address field that stores a phone number. Its input opens a phone keypad instead of a text keyboard |
| **Hidden address fields** | Fields left off the checkout. They stay in the control panel |
| **Hidden billing address fields** | Fields also left off a new billing address, such as a delivery switch, which a billing address does not use |
| **Required shipping address fields** | Fields the checkout requires on a shipping address, and on a saved address a customer edits, beyond the fields the address layout requires |
| **Required billing address fields** | The same for a new billing address |
| **Show a third address line** | Whether the form includes a third address line. Off by default |
| **Let customers name a saved address** | Whether the label of a saved address is editable at the checkout. Off by default |
| **Show the address label** | Whether the checkout shows a saved address by the name the customer gave it, in the address choices and the completed steps. Off by default |

## Saved addresses

The checkout lists the most recently updated addresses, up to **Saved address limit**, with the primary address first. Set the limit to zero to list every saved address. The checkout also lists the addresses the cart already uses, even past the limit, to show which address the cart bills or ships to. The billing choices leave out the saved address the cart ships to when the page loads, because **Same as shipping address** already selects that address.

### List or dropdown

A customer who orders for their own clients can have many saved addresses. **Saved address display** sets how the checkout shows them:

| Option | Shows |
| --- | --- |
| **List** | A row for each saved address |
| **Dropdown** | One searchable dropdown in each section |
| **Auto** | A list until the checkout lists more addresses than **Dropdown threshold**, then the dropdown |

With **Saved address limit** set to zero and **Saved address display** set to **Dropdown**, the checkout lists every saved address without lengthening the page.

In the dropdown, each saved address shows two lines: the recipient, plus the label when **Show the address label** is on, then the address. An address with no recipient or label shows the address alone, wrapped.

In the shipping section, the dropdown is labeled **Ship to** and lists the saved addresses, then **New shipping address**, then the pickup choice when [customer pickup](./customer-pickup.md) is on.

In the billing section, the dropdown lists **Same as shipping address**, the saved addresses, then **Use a different billing address**. A pickup order, or a checkout that does not ask for a shipping address, does not show **Same as shipping address**. Until the customer chooses a billing address, the single-page checkout keeps payment unavailable and lists **Billing address** as a missing detail. The stepped checkout does not continue past the billing step. On an order totaling zero, the single-page checkout does not ask for a billing address. For the exception, see [single-page checkout](./single-page-checkout.md#what-changes).

For a customer who can edit their saved addresses, **Edit** beside either dropdown opens the chosen saved address.

## Hidden and required fields

The checkout always shows the country and the address block, because Craft cannot resolve an address without them. The checkout also shows every field the address layout marks required, and the checkout settings cannot make that field optional.

A hidden field is never required, even if it is also selected in the required list.

**Hidden billing address fields** and **Required billing address fields** apply only to the new billing address form. A saved address the customer picks as billing keeps its values, and editing a saved address shows every field, because the address book has no billing or shipping side.

The hidden and required lists include custom fields you add to the address layout, alongside the native ones. The checkout renders a custom field only when its type is one of these: plain text, number, dropdown, radio buttons, checkboxes, lightswitch, date and time.

## The country sets the rest of the form

Each country has an address format naming which fields it uses. For France, the form keeps the second address line and the postal code, and hides the state field. The form changes as the customer picks a country.

With **Default country** set, the form opens on that country's format. An address the customer is editing keeps its own country.

The state field is the exception to the country's format. It shows wherever the country has a list of states, provinces or regions, and its label reads State / Province for every country.

Craft's format for the United Kingdom has no administrative area, and Craft has no list of UK counties. The plugin adds the administrative area to UK addresses and supplies its own county list, so the state field shows for the United Kingdom too.

The third address line also has its own switch, **Show a third address line**, because nearly every country format lists that line. With the switch on, the line still shows only where the country's format uses it.

## Address labels

An address's label is its name in the customer's address book. Commerce labels the addresses attached to an order "Shipping Address" or "Billing Address", and a new address the customer saves during checkout keeps that label.

With **Let customers name a saved address** on, the label is an editable field when a customer edits an address they already saved. The new address form leaves the label out, because Commerce replaces the label of a new address.

The checkout never overwrites labels a store sets for its own purposes. To stop customers from editing those labels, leave **Let customers name a saved address** off.

## Address verification and suggestions

Two tools help with the shipping address, both set at **Checkout -> Addresses**.

| Setting | Controls |
| --- | --- |
| **Verify shipping addresses** | Whether Avalara checks the shipping address and suggests its corrected version, which the customer can accept or decline. Needs the AvaTax plugin with its **Enable address validation** setting on. Off by default |
| **Address suggestions** | Whether the checkout lists matching addresses under the shipping address as the customer types. Picking one fills the form. Off by default |
| **Address suggestion provider** | Google Places or Loqate. Google Places by default |
| **Address suggestion API key** | The provider's key, set as an environment variable name |
| **Test the connection** | Sends one lookup to the provider and names the error. This is the only place a wrong key appears |

Use verification or suggestions, not both. Avalara checks an address the customer entered, and suggestions help the customer enter one. A suggested address can differ from Avalara's corrected version, which can confuse the customer, depending on their address. Only one runs at a time: while verification is running, the checkout skips suggestions.

### Avalara coverage

Avalara covers the United States and Canada. For other countries it answers `Country not supported`, which the checkout treats as no suggestion. A store shipping elsewhere gets no verification and no message saying why.

### Setting up suggestions

The plugin does not include a provider account. Supply your own account and key. Set the key as an environment variable so it stays out of project config:

```sh
FC_ADDRESS_LOOKUP_KEY=your-key-here
```

Then set **Address suggestion API key** to `$FC_ADDRESS_LOOKUP_KEY`, not the key itself.

With a revoked key, an exhausted account or an unreachable provider, the checkout does not list suggestions and writes the reason to the log.

### Limiting provider costs

Both providers bill per lookup, and the endpoint that calls them is public. **Set a spending limit at the provider.** In Google Cloud, set a daily and a per-minute quota on the Places API. Loqate sells prepaid credit. The plugin sets no rate limit of its own.
