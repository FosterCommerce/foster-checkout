# Custom includes

The plugin can include three templates of your own: one in the document head, one before the closing body tag, and one above the checkout summary. Use the head and body includes for analytics, tracking pixels or support widgets. Use the summary include for a message about the order, such as how much more the customer needs to spend for free shipping.

## Setting the paths

**Control panel:** **Checkout -> General**, under **Head include**, **Body include** and **Summary include**. Enter a template path relative to your templates directory, without the file extension.

**Config file:** set the `includes` key in `config/foster-checkout.php`.

```php
'includes' => [
	'head' => '_includes/checkout/head',
	'body' => '_includes/checkout/body',
	'summary' => '_includes/checkout/summary',
],
```

A key set in the config file locks that one field; the other two stay editable. For how the config file overrides the control panel, see [what overrides what](../reference/settings.md#what-overrides-what).

When you save in the control panel, the plugin rejects a **Head include** or **Body include** path that matches no template. The plugin does not check **Summary include**, or any path set in the config file. A path to a missing template throws an error on every page that renders the include.

## What your template receives

| Variable | Value |
| --- | --- |
| `context` | `cart` or `checkout` |
| `location` | `head`, `body` or `summary` |
| `step` | `email`, `shipping-address`, `shipping-method`, `billing`, `payment`, `confirmation`, `single-page`, or empty on the cart |
| `cart` | The current order. Passed to the head and summary includes on every page. The body include receives it on every page except the shipping address step |
| `firstView` | On the confirmation page only. `true` the first time the plugin renders the order's confirmation page, for any visitor, and `false` after that. An order completed before the update to Foster Checkout 2.1.0 gets `true` the next time its confirmation page renders |

The head and body includes render on every cart and checkout page, so check `step` to target one page. The summary include renders wherever the checkout summary does: `single-page` on the single-page checkout, and the step's name on each step of the stepped checkout.

```twig
{% if step == 'confirmation' and firstView %}
	{# Purchase tracking goes here #}
{% endif %}
```

## Edge cases

**Only the variables in the table are a contract.** The includes also inherit the surrounding template's variables, which is why `order` is available on the confirmation page although the plugin does not pass it. Those inherited variables can change in any release.

**The summary include renders once, on page load.** It does not re-render when the single-page checkout changes the shipping method or coupon.

**The summary include gets a bordered container only when its template outputs markup.** A template that outputs an empty string leaves no empty box. Put the condition inside the template rather than setting the include path conditionally.
