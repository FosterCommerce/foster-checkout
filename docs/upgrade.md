# Upgrading

What to change on a site when updating Foster Checkout. Run migrations after every update:

```sh
./craft migrate/all
```

## Upgrading to the next release

### Extra parameters removed

**Breaking.** **Extra parameters** at **Checkout -> Gateways**, and the `paymentGateways.<handle>.params` setting behind it, are removed. The plugin logs and ignores a `params` key, whether it was stored from the control panel or set in a config file.

Set the matching gateway setting instead:

- Stripe: **Payment method layout**, **Payment method order** and **Include Link**.
- PayPal: **Hidden funding sources**, **Extra funding sources**, **Turned away card brands**, **Locale** and **SDK components**.

For any other key, or one that depends on the order, see [payment form parameters](./dev-guide/payment-form-params.md).

### Login and register pages removed

**Breaking.** The checkout no longer serves `<checkout>/login` or `<checkout>/register`, and the **Account** note at **Checkout -> Notes & Links** is removed. **Sign in** goes to Craft's `loginPath`. Link to your own sign-in and registration pages instead.

### Klaviyo tracking off on update

**Klaviyo tracking** at **Checkout -> Features** is off after the update. To keep reporting to Klaviyo and offering the newsletter checkbox, turn it on.

### Voucher form action

The voucher form posts to `foster-checkout/voucher/add-code` and accepts gift voucher codes only. If a site template overrides the payment step, change its voucher form to that action.

### Save for later removed

**Save for later** is removed from **Checkout -> Line Items** and the cart. The plugin ignores a stored or configured value for it.

### Address settings need Manage checkout settings

**Verify shipping addresses** and the address suggestion settings are on **Checkout -> Addresses**, which needs **Manage checkout settings** rather than **Manage checkout features**. Grant it to any user group that edits them.

### Deprecated `lineItemImageField()`

`Checkout::lineItemImageField()` is deprecated. Call `lineItemImageFields()`, which returns every configured image field for a product type, variant first.

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
