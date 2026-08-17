# Settings

Every setting is editable in the control panel under **Checkout**. A site may also ship a `config/foster-checkout.php` file, which overrides the control panel.

## Screens

| Screen | Config key | Holds |
| --- | --- | --- |
| **Appearance** | `branding` | Brand colour, header background, Google font family, logo path, component style, title prefix |
| **Features** | `options` | Checkout page layout format (`enableSinglePageCheckout`; off = multi-page, on = single-page), the other `enable*` switches, and the Klaviyo list ID. Blank list ID hides the newsletter checkbox. Multi-page is the default |
| **Products** | `products` | Per product type, the field holding the cart preview image. Blank falls back to the product's own image |
| **Gateways** | `paymentGateways` | Per gateway: a field layout, layout columns, extra payment form parameters |
| **General** | `paths` and the keys below | Cart, checkout, account and cancel paths, plus the built-in cart template switch |

Keys on the General screen that are overridden independently of `paths`:

| Setting | Config key | Holds |
| --- | --- | --- |
| Head include, Body include | `includes` | Template paths injected into every cart and checkout page. See [custom includes](../dev-guide/custom-includes.md) |
| Priority countries | `priorityCountries` | Country codes shown at the top of country dropdowns, in the order listed |
| Zero value gateways | `zeroValueGatewayHandles` | Gateways available when an order totals zero |
| Customer order notes field | `customerOrderNotesFieldHandle` | Field on Orders holding the customer's note. Blank hides the order notes form |
| Content translation method | `contentTranslationMethod` | See below |

## What overrides what

Craft merges the config file over stored settings on every request, so a key set in the file always wins. Those fields are shown disabled with a warning naming the setting, and the save is rejected server side as well.

> This is being overridden by the `branding` setting in the `config/foster-checkout.php` file.

The merge is shallow and applies per top-level key: one value inside `options` in the config file replaces the whole `options` section. That is why the warning names a top-level setting rather than a single field. Remove the key from the config file to make the section editable again.

A gateway's field layout is stored outside plugin settings, so it stays editable even where `paymentGateways` is set in the config file.

## Content translation method

How checkout copy varies across sites, using Craft's own field translation methods. On a single-site install all three behave identically.

| Value | Behaviour |
| --- | --- |
| `none` | One copy shared by every site |
| `site` | A copy per site (default) |
| `language` | Sites speaking the same language share a copy |

## Gateway field layouts

A gateway's fields are chosen with Craft's field layout designer, which sets their order, width, label and whether each is required.

**Only fields already on the order can be used.** An order saves values through its own field layout, so a field missing from it would render, accept what the customer types, then be discarded with no error. Saving such a layout is rejected and names the offending handles. Add the field under **Commerce -> System Settings -> Order Fields** first.

A field's placeholder and its length or value limits live on the field itself, not the layout, so they apply everywhere that field is used.

## Settings that stay in config

| Setting | Why |
| --- | --- |
| A gateway `note` | May be a PHP closure, which the control panel cannot represent. The plugin falls back to it whenever the stored copy for that gateway is empty |
| `deliveryDate.estimate`, `deliveryDate.display` | Accept a closure or a Twig string, because they compute a date and a visibility flag rather than holding copy |
