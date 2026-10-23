# Notes and links

**Checkout -> Notes & Links** holds the notes on the cart and each checkout step, the newsletter checkbox label, the create account description, payment and shipping method notes, and the footer links. The plugin stores this copy in its own database table, not project config, so the copy stays editable on production.

Buttons, field labels and messages are translations. To change one, see [write your copy](../getting-started.md#6-write-your-copy).

## What you can edit

The screen is grouped by the panel each piece of copy appears in.

| Group | Holds |
| --- | --- |
| **Global** | The note shown on every checkout step, and the **footer links** shown at the bottom of the cart and checkout pages |
| **Cart** | The cart note, and the one shown when the cart is empty |
| **Email step** | The step's note, the **newsletter checkbox label**, and the **create account description** shown under the create account checkbox. On the stepped checkout, a signed-in customer sees this step only when the newsletter checkbox is shown, with their email in place of the email field and no create account checkbox |
| **Shipping address step** | The step's note |
| **Shipping method step** | The step's note, the **no shipping methods note** shown when no shipping method is available for the address, and **notes for each shipping method** |
| **Billing step** | The step's note |
| **Payment step** | The step's note, and **payment method notes**, one per gateway configured in Commerce, shown when a customer picks that method. The name a customer reads for a gateway is set at **Checkout -> Gateways**, not here |
| **Order confirmation** | The confirmation note |

## HTML and Twig

Notes accept HTML. Each one is also rendered as a Twig template, so copy can reference the cart or the order:

```twig
<p>{{ cart.totalQty }} items in your cart.</p>
```

Each note receives different variables:

| Copy | Receives |
| --- | --- |
| Cart, empty cart, step, no shipping methods, and payment method notes | `cart` |
| Order confirmation note | `order` |
| Shipping method notes | `cart` and `shippingMethod` |
| Global note, newsletter checkbox label, create account description | Neither |

With Craft's dev mode on, a reference to a variable the note does not receive throws an error.

The newsletter checkbox label, the create account description and shipping method notes show as plain text: the checkout strips their HTML tags.

Because notes run as Twig, editing them is equivalent to template access. See [permissions](../reference/permissions.md).

## Shipping method notes

Each shipping method set up in Commerce gets a field, and its note shows under the method's name. A method a shipping plugin adds at checkout gets a row under **Notes for other shipping methods**, keyed by its handle, such as `FEDEX_GROUND`. A row missing a handle or a note is dropped when you save.

To change a note per cart in code, see [shipping method notes](../dev-guide/shipping-method-notes.md).

## Multi-site

On a multi-site install a site selector appears in the breadcrumb. Copy is stored per site or per language depending on **Content translation method** at **Checkout -> General**, so switching sites shows that site's own copy. Filling one site does not fill the others.

The selector is hidden when the translation method is **Not translatable**, since every site shares one copy.

## Footer links

One row per link, with a label and a URL. Rows are reorderable, and the order is the order they appear on the storefront. A row missing either the label or the URL is dropped when you save.

Footer links are hidden on an empty cart.
