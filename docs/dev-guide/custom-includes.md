# Custom includes

Two of your own templates can be injected into every cart and checkout page: one into the document head, one before the closing body tag. Use them for analytics, tracking pixels, support widgets or anything else that has to appear on the checkout.

## Setting them

**Control panel:** **Checkout -> General**, under **Head include** and **Body include**. Enter a template path relative to your templates directory, without the file extension.

**Config file:** set the `includes` key in `config/foster-checkout.php`.

```php
'includes' => [
    'head' => '_includes/checkout/head',
    'body' => '_includes/checkout/body',
],
```

The config file wins over the control panel, and setting either key there makes both fields read-only. See [settings](../reference/settings.md).

A path with no template behind it is rejected when saved in the control panel. Set through the config file it is not checked, and a missing template throws on every cart and checkout page.

## What your template receives

| Variable | Value |
| --- | --- |
| `context` | `cart` or `checkout` |
| `location` | `head` or `body` |
| `step` | `email`, `shipping-address`, `shipping-method`, `billing`, `payment`, `confirmation`, `single-page`, or empty on the cart |
| `cart` | the current order. Reaches the head include on every page, but the body include omits it on the shipping address and order confirmation pages |

One template is included on every page, so branch on `step` to target a single one. On a one-page checkout the value is `single-page`.

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

**Keep them cheap.** They render on every page of the checkout, including the payment step.
