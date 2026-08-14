# Settings

Every setting is editable in the control panel under **Checkout**. A site may also ship a `config/foster-checkout.php` file, which overrides the control panel.

## What overrides what

Craft merges the config file over stored settings on every request, so a key set in the file always wins. The control panel shows those fields disabled, with a warning naming the setting:

> This is being overridden by the `branding` setting in the `config/foster-checkout.php` file.

The save is rejected server-side as well, since a disabled input is not a control.

The merge is shallow, and it applies per top-level key. Setting one value inside `options` in the config file replaces the whole `options` section, not just that value. This is why the warning names a top-level setting rather than a single field.

To make a section editable again, remove its key from the config file.

## Screens

### Appearance

Overrides the `branding` key. Brand colour, header background, Google font family, logo path, component style (rounded or flat) and title prefix.

### Features

Overrides the `options` key. The `enable*` switches, plus the Klaviyo list ID that the newsletter checkbox subscribes to. Leave the list ID blank to hide the checkbox.

Some content fields appear only when their feature is switched on. The "Made a mistake" heading and text are hidden on the Content screen unless **Made a mistake** is enabled.

### Products

Overrides the `products` key. One row per product type, naming the field that holds the preview image shown in the cart. Leave a handle blank to fall back to the product's own image.

### Gateways

Lists every gateway configured in Commerce. Click one to edit it.

Each gateway has a **field layout**: which of the order's fields a customer fills in when they pick that payment method, in what order, at what width, with what label and whether it is required. It is Craft's own field layout designer, so it works the way every other field layout in the control panel does.

Only fields that are already on the order can be used. An order saves values through its own field layout, so a field that is not on it would render, accept what the customer types, and then be discarded with no error. Saving a layout with such a field is rejected and names the offending handles. To use a new field, add it to the order's field layout first, under **Commerce -> System Settings -> Order Fields**.

A field's placeholder and its length or value limits are settings on the field itself, not on the layout, so they are shared everywhere that field is used.

Two settings sit outside the layout and come from plugin settings, so a config file overrides them:

- **Layout columns**: how many columns the gateway's fields are laid out across.
- **Extra parameters**: merged into the gateway's payment form parameters, such as PayPal SDK options.

The field layout itself is stored separately from plugin settings, so it stays editable even on a store whose config file sets `paymentGateways`.

### General

Overrides the `paths` key, plus four keys of its own that are overridden independently.

- **Paths**: site-relative paths for cart, checkout, account and cancel. Turn off **Use the built-in cart template** to serve your own template at the cart path.
- **Head include** and **Body include** (`includes`): template paths injected into the cart and checkout pages. A path with no template behind it is rejected on save, because it would otherwise throw on every cart and checkout page.
- **Priority countries** (`priorityCountries`): country codes shown at the top of country dropdowns, in the order listed.
- **Zero value gateways** (`zeroValueGatewayHandles`): gateways available when an order totals zero.
- **Customer order notes field** (`customerOrderNotesFieldHandle`): handle of the field on Orders holding the note a customer leaves. Leave blank to hide the order notes form.
- **Content translation method** (`contentTranslationMethod`): see below.

## Content translation method

Controls how checkout copy varies across sites on a multi-site install. It uses Craft's own field translation methods.

| Value | Behaviour |
| --- | --- |
| `none` | One copy shared by every site |
| `site` | A copy per site (default) |
| `language` | Sites speaking the same language share a copy |

On a single-site install all three behave identically.

## Settings that stay in config

A gateway note may be defined as a PHP closure, which the control panel cannot represent. Where one exists, leave it in the config file: the plugin falls back to it whenever the stored copy for that gateway is empty.

The same applies to `deliveryDate.estimate` and `deliveryDate.display`, which accept a closure or a Twig string because they compute a date and a visibility flag rather than holding copy.
