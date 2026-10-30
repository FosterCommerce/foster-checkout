# Upgrading

What to change on a site when updating Foster Checkout. Run migrations after every update:

```sh
./craft migrate/all
```

## Unreleased

### Imager X transform config renamed

`lineItems.imagerXConfig` is now `lineItems.imageTransformConfig`. If your config file uses the old key, the plugin still reads it and writes a warning to Craft's log. To stop the warning, rename the key in your config file.

A template that reads `lineItems.imagerXConfig` still works, and the plugin writes the same warning. To stop it, read `lineItems.imageTransformConfig` instead.

### Foster Checkout completes paid carts

When a customer opens a checkout page or the built-in cart page, Foster Checkout completes a paid cart that isn't a completed order. The customer then sees the order confirmation page. If Foster Checkout can't complete the order, those pages show a notice asking the customer to contact you, with their cart number and a **Start a new cart** button.

Until the order completes, `commerce/cart/update-cart`, `commerce/payments/pay`, `foster-checkout/voucher/add-code`, `gift-voucher/cart/add-code` and `gift-voucher/cart/remove-code` first try to complete the order, then reject the request. A JSON request gets a 400 response with `message` and `error`, plus a `redirect` to the confirmation page when Foster Checkout completed the order. If your own cart template sends JSON requests, follow `redirect` when the response includes it. `commerce/cart/forget-cart` and `foster-checkout/cart/delete` also try to complete the order first. If that fails, they clear the cart anyway. The customer can then shop again while you resolve the paid cart in the control panel.

Foster Checkout does not block payments made in the control panel.

If you serve your own cart template at the cart path, loading it does not complete the order or show the notice. Only the checkout pages and the built-in cart page do.

### Estimated shipping setting removed

A migration removes `options.enableEstimatedShipping` from the stored settings. If your config file sets the key, the plugin logs a warning and ignores the value. Remove the key from your config file.

### Cart and checkout pages excluded from Blitz

The cart and checkout pages now send no-cache headers, and Foster Checkout excludes them from Blitz's cache. If your web server serves Blitz's cached files directly, clear the pages Blitz cached before updating:

```sh
./craft blitz/cache/clear-urls "https://example.com/cart*,https://example.com/checkout*"
```

## Upgrading to 2.0.0

### Extra parameters removed

**Extra parameters** at **Checkout -> Gateways**, and the `paymentGateways.<handle>.params` setting behind it, are removed. The plugin logs and ignores a `params` key, whether it was stored from the control panel or set in a config file.

Set the matching gateway setting instead:

- Stripe: **Payment method layout**, **Payment method order** and **Include Link**.
- PayPal: **Hidden funding sources**, **Extra funding sources**, **Turned away card brands**, **Locale** and **SDK components**.

For any other key, or one that depends on the order, see [payment form parameters](./dev-guide/payment-form-params.md).

### Login and register pages removed

The checkout no longer serves `<checkout>/login` or `<checkout>/register`, and the **Account** note at **Checkout -> Notes & Links** is removed. **Sign in** goes to Craft's `loginPath`. Link to your own sign-in and registration pages instead.

### Newsletter checkbox

**Newsletter checkbox** at **Checkout -> Features** is off after the update, and the checkbox subscribes customers through Klaviyo Connect 7.3.0 or later rather than Klaviyo Connect Plus. The checkout ignores an earlier Klaviyo Connect. To keep showing the checkbox, turn the setting on. Then install or update Klaviyo Connect, or subscribe customers from a site module as described in [newsletter services](./dev-guide/newsletter-services.md).

The cart and checkout no longer send Started Checkout events to Klaviyo. To keep sending them, see [Klaviyo Connect's documentation](https://www.fostercommerce.com/craft-cms-plugins/klaviyo-connect/docs).

### Klaviyo list ID renamed

**Klaviyo list ID** is now **Newsletter list ID**, and `options.klaviyoListId` is now `options.newsletterListId`. If a stored value or your config file uses the old key, the plugin still reads it and writes a warning to Craft's log. To stop the warning, rename the key in your config file. For a value saved in the control panel, save **Checkout -> Features** once, which stores it under the new key.

### `klaviyoTrackingEnabled()` removed

`craft.fostercheckout.klaviyoTrackingEnabled()` is removed. To check whether the checkout shows the newsletter checkbox, call `craft.fostercheckout.offersNewsletter(cart)`. To skip a template's own Klaviyo calls on an order placed for another customer, make the calls only when `not currentUser or currentUser.email == cart.email`.

### Saved addresses limited to 10

The checkout lists 10 of a customer's saved addresses: their primary address, then the most recently updated, plus the addresses the cart uses. To list every saved address, set **Saved address limit** at **Checkout -> Addresses** to `0`.

### Voucher form action

The voucher form posts to `foster-checkout/voucher/add-code` and accepts gift voucher codes only. If a site template overrides the payment step, change its voucher form's action to `foster-checkout/voucher/add-code`.

### Save for later removed

**Save for later** is removed from **Checkout -> Line Items** and the cart. The plugin ignores a stored or configured value for it.

### Settings moved off the Features screen

**Checkout layout** and **Page transitions** are on **Checkout -> Appearance**, which needs **Manage checkout appearance**. **Verify shipping addresses**, the address suggestion settings, and **Placeholder images** are on **Checkout -> Addresses** and **Checkout -> Line Items**, which need **Manage checkout settings**. Grant **Manage checkout appearance** and **Manage checkout settings** to any user group that had only **Manage checkout features**.

### `lineItemImageField()` removed

`Checkout::lineItemImageField()` is removed. Call `lineItemImageFields()`, which returns every configured image field for a product type, variant first. Its first entry is the field `lineItemImageField()` returned. In Twig, `craft.fostercheckout.lineItemImageField(type)` becomes `craft.fostercheckout.lineItemImageFields(type)|first`.

## Upgrading to 1.0.0

### Checkout copy moved to Notes & Links

A migration copies checkout copy from the entry and global set fields named by the `notes` and `links` config keys into **Checkout -> Notes & Links**. The migration does not overwrite copy already entered there, so you can run it again.

After it runs, remove `notes` and `links` from the config file. Two exceptions:

- A gateway note written as a PHP closure stays in the config file, because the control panel cannot store it.
- The plugin still reads `notes.customersOrderNotes.fieldHandle`. Before removing it, set **Customer order notes field** under **Checkout -> Custom Fields**.

### Gateway fields converted to field layouts

A migration converts each gateway's `fields` config into a field layout at **Checkout -> Gateways**. A placeholder or length limit moves onto the plain text field itself. The migration skips a gateway whose layout already has fields. After it runs, remove `fields` from the config file; the plugin logs a warning for the key and ignores it.

### Removed config keys

The plugin logs a warning for `options.enableFreeShippingMessage` and `options.enableMadeAMistake`, and ignores them. Remove them from the config file.
