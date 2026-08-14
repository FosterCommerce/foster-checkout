# Content

All checkout copy is edited at **Checkout -> Content**. It is stored in the plugin's own database table, not project config, so it stays editable on production.

## What you can edit

- **Notes** for each step: cart, empty cart, login, email, shipping address, shipping method, billing, payment, order confirmation, and a global note shown on every checkout step.
- **Newsletter checkbox label**, and the **delivery date** label and message.
- **Payment method notes**, one per gateway configured in Commerce, shown when a customer picks that method.
- **Footer links**, shown at the bottom of the cart and checkout pages.

Fields for optional features appear only when that feature is on. The "Made a mistake" heading and text are hidden unless **Made a mistake** is enabled under **Checkout -> Features**.

## HTML and Twig

Notes accept HTML. Each one is also rendered as a Twig template, so copy can reference the cart or the order:

```twig
<p>Your cart has {{ cart.totalQty }} item(s).</p>
```

A note that references something unavailable on that page will throw, so keep references to what the page has: `cart` on cart and checkout steps, `order` on the confirmation page.

Because notes run as Twig, editing them is equivalent to template access. See [permissions](../reference/permissions.md).

## Multi-site

On a multi-site install a site selector appears in the breadcrumb. Copy is stored per site or per language depending on the content translation method, so switching sites shows that site's own copy. Filling one site does not fill the others.

The selector is hidden when the translation method is `none`, since every site shares one copy.

## Footer links

One row per link, with a label and a URL. Rows are reorderable, and the order is the order they appear on the storefront. A row missing either the label or the URL is dropped when you save.

Footer links are hidden on an empty cart.

## Existing sites

Sites that previously kept this copy in an entry or global set field, mapped through `notes` and `links` in `config/foster-checkout.php`, have it copied across automatically when the plugin updates. The migration fills only what has not already been entered in the control panel, so it is safe to run more than once.

The old `notes` and `links` config keys are ignored after that. `notes.customersOrderNotes.fieldHandle` is the exception: it names an order field rather than holding copy, and is still read, though its home is now **Customer order notes field** under **Checkout -> General**.
