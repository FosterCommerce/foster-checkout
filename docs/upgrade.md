# Upgrading

What to change on a site when updating Foster Checkout. Run migrations after every update:

```sh
./craft migrate/all
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

**Newsletter checkbox** at **Checkout -> Features** is off after the update, and the checkbox subscribes customers through Klaviyo Connect 7.3.0 or later rather than Klaviyo Connect Plus. The checkout ignores an earlier Klaviyo Connect. To keep offering the checkbox, turn the setting on, then install or update Klaviyo Connect, or subscribe customers from a site module, as described in [newsletter services](./dev-guide/newsletter-services.md).

The cart and checkout no longer send Started Checkout events to Klaviyo. To keep sending them, see [Klaviyo Connect's documentation](https://www.fostercommerce.com/craft-cms-plugins/klaviyo-connect/docs).

### Klaviyo list ID renamed

**Klaviyo list ID** is now **Newsletter list ID**, and `options.klaviyoListId` is now `options.newsletterListId`. A stored value or config file using the old key still works, and writes a warning to Craft's log. To stop the warning, rename the key in your config file. For a value saved in the control panel, save **Checkout -> Features** once, which stores it under the new key.

### `klaviyoTrackingEnabled()` removed

`craft.fostercheckout.klaviyoTrackingEnabled()` is removed. To check whether the newsletter checkbox is offered, call `craft.fostercheckout.offersNewsletter(cart)`. To keep a template's own Klaviyo calls off an order placed for another customer, make them only when `not currentUser or currentUser.email == cart.email`.

### Saved addresses limited to 10

The checkout offers 10 of a customer's saved addresses: their primary address, then the most recently updated, plus the addresses the cart uses. To offer every saved address, set **Saved address limit** at **Checkout -> Addresses** to `0`.

### Voucher form action

The voucher form posts to `foster-checkout/voucher/add-code` and accepts gift voucher codes only. If a site template overrides the payment step, change its voucher form to that action.

### Save for later removed

**Save for later** is removed from **Checkout -> Line Items** and the cart. The plugin ignores a stored or configured value for it.

### Settings moved off the Features screen

**Checkout layout** and **Page transitions** are on **Checkout -> Appearance**, which needs **Manage checkout appearance**. **Verify shipping addresses**, the address suggestion settings, and **Placeholder images** are on **Checkout -> Addresses** and **Checkout -> Line Items**, which need **Manage checkout settings**. Grant these to any user group that held only **Manage checkout features**.

### `lineItemImageField()` removed

`Checkout::lineItemImageField()` is removed. Call `lineItemImageFields()`, which returns every configured image field for a product type, variant first. Its first entry is the field `lineItemImageField()` returned. In Twig, `craft.fostercheckout.lineItemImageField(type)` becomes `craft.fostercheckout.lineItemImageFields(type)|first`.

## Upgrading to 1.0.0

### Checkout copy moved to Notes & Links

A migration copies checkout copy from the entry and global set fields named by the `notes` and `links` config keys into **Checkout -> Notes & Links**. Copy already entered there is left alone, so the migration is safe to run again.

After it runs, remove `notes` and `links` from the config file. Two exceptions:

- A gateway note written as a PHP closure stays in the config file, because the control panel cannot store it.
- The plugin still reads `notes.customersOrderNotes.fieldHandle`. Before removing it, set **Customer order notes field** under **Checkout -> Custom Fields**.

### Gateway fields converted to field layouts

A migration converts each gateway's `fields` config into a field layout at **Checkout -> Gateways**. A placeholder or length limit moves onto the plain text field itself. A gateway whose layout already has fields is left alone. After it runs, remove `fields` from the config file; the plugin logs a warning for the key and ignores it.

### Removed config keys

The plugin logs a warning for `options.enableFreeShippingMessage` and `options.enableMadeAMistake`, and ignores them. Remove them from the config file.
