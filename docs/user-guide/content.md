# Notes and links

**Checkout -> Notes & Links** holds the notes on the cart and each checkout step, the newsletter checkbox label, the create account description, payment method notes and the footer links. The plugin stores this copy in its own database table, not project config, so the copy stays editable on production.

Buttons, field labels and messages are translations. To change one, see [write your copy](../getting-started.md#5-write-your-copy).

## What you can edit

The screen is grouped by the panel each piece of copy appears in.

| Group | Holds |
| --- | --- |
| **Global** | The note shown on every checkout step, and the **footer links** shown at the bottom of the cart and checkout pages |
| **Cart** | The cart note, and the one shown when the cart is empty |
| **Email step** | The step's note, the **newsletter checkbox label**, and the **create account description** shown under the create account checkbox. On the stepped checkout, a signed-in customer skips this step and sees the newsletter checkbox on the address step, or on billing when no item ships |
| **Shipping address step** | The step's note |
| **Shipping method step** | The step's note, and the **no shipping methods note** shown when no shipping method is available for the address |
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
| Global note, newsletter checkbox label, create account description | Neither |

With Craft's dev mode on, a reference to a variable the note does not receive throws an error.

The newsletter checkbox label and the create account description show as plain text: the checkout strips their HTML tags.

Because notes run as Twig, editing them is equivalent to template access. See [permissions](../reference/permissions.md).

## Multi-site

On a multi-site install a site selector appears in the breadcrumb. Copy is stored per site or per language depending on **Content translation method** at **Checkout -> General**, so switching sites shows that site's own copy. Filling one site does not fill the others.

The selector is hidden when the translation method is **Not translatable**, since every site shares one copy.

## Footer links

One row per link, with a label and a URL. Rows are reorderable, and the order is the order they appear on the storefront. A row missing either the label or the URL is dropped when you save.

Footer links are hidden on an empty cart.
