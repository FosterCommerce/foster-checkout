# Notes and links

**Checkout -> Notes & Links** sets the notes on the cart and each checkout step, the newsletter checkbox label, the create account description, payment and shipping method notes, and the footer links. The plugin stores this copy in its own database table, not project config, so the copy stays editable on production.

Button text, field labels and messages come from the plugin's translation file. To change one, see [write your copy](../getting-started.md#6-write-your-copy).

## What you can edit

The screen groups the copy by where it appears.

| Group | Contains |
| --- | --- |
| **Global** | The note shown on every checkout step, and the **footer links** shown at the bottom of the cart and checkout pages |
| **Cart** | The cart note, and the empty cart note |
| **Email step** | The step's note, the **newsletter checkbox label**, and the **create account description** shown under the create account checkbox. On the stepped checkout, a signed-in customer sees their email in place of the email field and no create account checkbox. For when the checkout skips this step, see [checkout fields](./checkout-fields.md) |
| **Shipping address step** | The step's note |
| **Shipping method step** | The step's note, the **no shipping methods note** shown when no shipping method is available for the address, and **notes for each shipping method** |
| **Billing step** | The step's note |
| **Payment step** | The step's note, and **payment method notes**, one per gateway configured in Commerce, shown when a customer picks that method. To change the name a customer reads for a gateway, use **Checkout -> Gateways** |
| **Order confirmation** | The confirmation note |

## HTML and Twig

Notes accept HTML. The checkout also renders each note as a Twig template, so a note can reference the cart or the order:

```twig
<p>{{ cart.totalQty }} items in your cart.</p>
```

Each note receives different variables:

| Copy | Receives |
| --- | --- |
| Cart, empty cart, step, no shipping methods, and payment method notes | `cart` |
| Order confirmation note | `order` |
| Shipping method notes | `cart` and `shippingMethod` |
| Global note, newsletter checkbox label, create account description | No variables |

With Craft's dev mode on, a reference to a variable the note does not receive throws an error.

The newsletter checkbox label, the create account description and shipping method notes show as plain text: the checkout strips their HTML tags.

Because notes run as Twig, permission to edit them is equivalent to template access. For that permission, see [permissions](../reference/permissions.md).

## Shipping method notes

Each shipping method set up in Commerce gets a field, and its note shows under the method's name. A method a shipping plugin adds at checkout gets a row under **Notes for other shipping methods**, keyed by its handle, such as `FEDEX_GROUND`. When you save, the plugin removes any row missing a handle or a note.

To change a note per cart in code, see [shipping method notes](../dev-guide/shipping-method-notes.md).

## Multi-site

On a multi-site install, a site selector appears in the breadcrumb. The plugin stores copy per site or per language, depending on **Content translation method** at **Checkout -> General**, so switching sites shows that site's own copy. Filling in one site's copy does not fill in the other sites.

The screen hides the selector when the translation method is **Not translatable**, because every site shares one copy.

## Footer links

One row per link, with a label and a URL. The cart and checkout pages show the links in row order. When you save, the plugin removes any row missing either the label or the URL.

The empty cart page does not show footer links.
