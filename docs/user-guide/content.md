# Notes and links

All checkout copy is edited at **Checkout -> Notes & Links**. It is stored in the plugin's own database table, not project config, so it stays editable on production.

## What you can edit

The screen is grouped by the panel each piece of copy appears in.

| Group | Holds |
| --- | --- |
| **Global** | The note shown on every checkout step, and the **footer links** shown at the bottom of the cart and checkout pages |
| **Cart** | The cart note, and the one shown when the cart is empty |
| **Account** | The note on the login and register pages |
| **Email step** | The step's note and the **newsletter checkbox label** |
| **Shipping address step** | The step's note |
| **Shipping method step** | The step's note, and the **no shipping methods note** shown when nothing can be quoted for the address |
| **Billing step** | The step's note |
| **Payment step** | The step's note, and **payment method notes**, one per gateway configured in Commerce, shown when a customer picks that method. The name a customer reads for a gateway is set at **Checkout -> Gateways**, not here |
| **Order confirmation** | The confirmation note |

## HTML and Twig

Notes accept HTML. Each one is also rendered as a Twig template, so copy can reference the cart or the order:

```twig
<p>Your cart has {{ cart.totalQty }} item(s).</p>
```

A note that references something unavailable on that page throws, so keep references to what the page has: `cart` on cart and checkout steps, `order` on the confirmation page.

Because notes run as Twig, editing them is equivalent to template access. See [permissions](../reference/permissions.md).

## Multi-site

On a multi-site install a site selector appears in the breadcrumb. Copy is stored per site or per language depending on the content translation method, so switching sites shows that site's own copy. Filling one site does not fill the others.

The selector is hidden when the translation method is `none`, since every site shares one copy.

## Footer links

One row per link, with a label and a URL. Rows are reorderable, and the order is the order they appear on the storefront. A row missing either the label or the URL is dropped when you save.

Footer links are hidden on an empty cart.

