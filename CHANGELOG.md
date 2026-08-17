# Release Notes for Foster Checkout

## Unreleased

### Added

- `enableSinglePageCheckout` setting on **Checkout -> Features** (`Checkout page layout format`). Off is multi-page, on is single-page.
- Checkout copy is now edited at **Checkout -> Content**, stored in the plugin's own table so it stays editable on production. Covers step notes, the newsletter checkbox label, delivery date copy, payment method notes and footer links.
- Copy is stored per site or per language, set by the new `contentTranslationMethod` setting.
- Control panel screens for appearance, features, products, payment gateways and general settings, so a store can run without a config file.
- Permissions: `foster-checkout-viewContent`, `foster-checkout-editContent`, `foster-checkout-manageAppearance`, `foster-checkout-manageFeatures` and `foster-checkout-manageSettings`.
- `customerOrderNotesFieldHandle` setting, replacing `notes.customersOrderNotes.fieldHandle`.
- Head and body includes are validated on save, so a path with no template behind it can no longer take the storefront down.
- Payment gateways now use Craft's field layout designer. **Checkout -> Gateways** lists the gateways configured in Commerce, and each one opens its own screen with a field layout, layout columns and extra parameters.

### Changed

- The `notes` and `links` config keys are retired. Existing values are migrated into content storage on update, and the old keys are then ignored. `notes.customersOrderNotes.fieldHandle` is still read for backwards compatibility.
- A gateway note, the newsletter label and the delivery date label and message now read from content storage, falling back to their config values when no copy is stored. A gateway note defined as a PHP closure keeps working through that fallback.
- Settings saved in the control panel no longer round-trip through the settings model, which previously dropped values the model could not represent.
- A gateway's `fields` config is converted into a field layout on update. A field's placeholder and maximum length move onto the Craft field, since a layout cannot express them.
- A gateway field must now be a field on the order. Previously a handle that was not on the order rendered an input whose value the order silently discarded; saving such a layout is now rejected.

## 1.0.0

- Initial release
