# Custom includes

Three of your own templates can be injected: one into the document head, one before the closing body tag, and one above the checkout summary. Use the first two for analytics, tracking pixels, support widgets or anything else that has to appear on the checkout, and the third for a message about the order, such as how much more it takes to earn free shipping.

## Setting them

**Control panel:** **Checkout -> General**, under **Head include**, **Body include** and **Summary include**. Enter a template path relative to your templates directory, without the file extension.

**Config file:** set the `includes` key in `config/foster-checkout.php`.

```php
'includes' => [
    'head' => '_includes/checkout/head',
    'body' => '_includes/checkout/body',
    'summary' => '_includes/checkout/summary',
],
```

The config file wins over the control panel, and setting any of the keys there makes all three fields read-only. See [settings](../reference/settings.md).

A path with no template behind it is rejected when saved in the control panel. Set through the config file it is not checked, and a missing template throws on every cart and checkout page.

## What your template receives

| Variable | Value |
| --- | --- |
| `context` | `cart` or `checkout` |
| `location` | `head`, `body` or `summary` |
| `step` | `email`, `shipping-address`, `shipping-method`, `billing`, `payment`, `confirmation`, `single-page`, or empty on the cart |
| `cart` | the current order. Passed to the head and summary includes on every page. The body include on the shipping address step is the one page that does not receive it |

The head and body includes render on every cart and checkout page, so branch on `step` to target a single one. The summary include renders wherever the checkout summary does: `single-page` on the one page layout, and the step's own name on each step of the stepped layout.

```twig
{% if context == 'checkout' and step == 'confirmation' %}
	{# Purchase tracking goes here, once the order exists #}
{% endif %}
```

## Tips

**Check `step` before anything that should happen once.** The includes run on every cart and checkout page, so a purchase event without that check fires repeatedly through the flow.

```twig
{% if step == 'confirmation' and craft.app.env == 'production' %}
```

**Only the four variables above are a contract.** The includes also inherit the surrounding template's variables, which is how `order` is reachable on the confirmation page even though the plugin never passes it. Anything beyond the four can move without warning.

**Keep them cheap.** The head and body includes render on every page of the checkout, including the payment step.

**The summary include renders once per page, server side.** Quantities are read-only at the checkout, so a total it reads from `cart` cannot change while the customer is on the page. Anything that has to follow a shipping method or coupon change belongs in the Alpine state instead.

**It gets its own bordered container, and only when it renders something.** A template that outputs nothing for this cart leaves no empty box, so branch inside it rather than setting the include conditionally.
