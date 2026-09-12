# Release Notes for Foster Checkout

## 1.3.0 - 2026-09-12

### Added

- Added a “Products that never ship” condition at **Checkout -> Products**, also settable as `notShippableProducts` in config. Variants of a matching product count as not shippable for Commerce, Postie and other shipping plugins.

### Changed

- The single-page checkout no longer shows the shipping address panel, the shipping method panel, the “same as shipping address” option or the shipping row when nothing in the cart ships. The address panel stays when the store requires a shipping address.
- The multi-page address and shipping steps now redirect past themselves when nothing in the cart ships.
- Required checkout fields at the shipping address and shipping method positions are no longer enforced when that step does not render.
- Postie rates are now fetched on the multi-page shipping step, not only on the single-page checkout.

## 1.2.2 - 2026-09-10

### Fixed

- Fixed footer padding of all pages


## 1.2.1 - 2026-09-10

### Fixed

- Fixed a deprecation warning that was logged on every request, even when no config file set the removed `fields` gateway setting.

## 1.2.0 - 2026-09-10

### Added

- Added `craft.fostercheckout.couponName()` and `craft.fostercheckout.couponMessages()`.

### Changed

- Improved the single-page checkout to name the discount next to an applied coupon code, as the cart and multi-page checkout do.
- Improved Advanced Discounts messages on the single-page checkout to update as a coupon is applied or removed, without reloading the page.

### Fixed

- Fixed a bug where only the first Advanced Discounts message was shown.
- Fixed an error that could occur on the cart and checkout when the applied coupon was an Advanced Discounts code, or a Commerce code whose discount had since been disabled or deleted.
- Fixed an error that could occur on the payment step and the single-page checkout when the cart's gateway was no longer available to the order.

## 1.1.0 - 2026-09-10

> {warning} Checkout address forms no longer offer a third address line. Turn on “Show a third address line” under **Checkout -> General** to keep it. Values already stored in the third line still print in formatted addresses.

### Added

- Added checkout for an order whose customer is not the signed-in user, so the address book, saved addresses and the contact shown all follow the order's customer. See [checkout for another customer](https://github.com/FosterCommerce/foster-checkout/blob/main/docs/dev-guide/checkout-for-another-customer.md).
- Added `Checkout::EVENT_DEFINE_CONTACT` for naming the contact the checkout shows in place of the order's email.
- Added `craft.fostercheckout.contact()`, `craft.fostercheckout.gatewayLabel()`, `craft.fostercheckout.canViewAddresses()`, `craft.fostercheckout.canSaveAddresses()` and `craft.fostercheckout.klaviyoTrackingEnabled()`.
- Added `CheckoutFieldLayouts::EVENT_DEFINE_CHECKOUT_FIELDS` and `CheckoutFieldLayouts::EVENT_APPLY_CHECKOUT_FIELDS` for adding a checkout field your own code stores. See [contributed fields](https://github.com/FosterCommerce/foster-checkout/blob/main/docs/dev-guide/contributed-fields.md).
- Added a “Show the stock count” switch under **Checkout -> Line Items**. On by default.
- Added a “Show a third address line” switch under **Checkout -> General**. Off by default.
- Added a “Let customers name a saved address” switch under **Checkout -> General**. Off by default.
- Added a “Name shown to customers” field per gateway under **Checkout -> Gateways**. Blank uses the gateway's own name.

### Changed

- Applying or removing a coupon on the single-page checkout now updates the line item prices without reloading the page.
- The single-page checkout now rebuilds the Stripe payment form only when the order total changes, so it flickers less as a customer edits their address and starts fewer payment transactions.
- Klaviyo events are no longer sent when the signed-in user's email is not the order's.
- A gateway's name is now translated in the `site` category rather than `foster-checkout`.
- Checkout address forms no longer offer a third address line unless “Show a third address line” is on under **Checkout -> General**.
- The checkout now fails with an error naming the store when that store has no countries selected, in place of rendering an empty country dropdown.

### Fixed

- Fixed a bug where saving a settings screen could clear settings that were still editable, when another setting in the same group was set in `config/foster-checkout.php`.
- Fixed a bug where saving a payment gateway cleared its extra parameters, when they were set in `config/foster-checkout.php`.
- Fixed an error that could occur when saving an address, if a field named in “Required address fields” was no longer in the address field layout.
- Fixed an error that occurred when an address's country was not one the store sells to.
- Fixed a bug where an address saved at the checkout was filed under the signed-in user rather than the order's customer.
- Fixed a bug where a customer with no saved addresses could not reach the new address form on the single-page checkout.
- Fixed a bug where the new address form opened pre-filled with the saved address the cart was already using.
- Fixed a bug where turning “Show line item options” on set the hidden option prefix to `1`.
- Fixed a bug where picking a saved address was ignored, on an order filed under someone other than the signed-in user.
- Fixed a bug where “Same as shipping address” did not copy the shipping address, on an order filed under someone other than the signed-in user.
- Fixed a bug where picking a saved billing address on the single-page checkout was not saved until some later change.
- Fixed a bug where two of the three billing address choices could appear selected at once.
- Fixed a bug where a billing address changed after the payment form loaded was charged against the previous address.
- Fixed an error that could occur on the single-page checkout while a Stripe payment form was still loading.
- Fixed an error that occurred on the single-page checkout when PayPal Checkout was installed and another gateway was selected.

## 1.0.0 - 2026-09-04

### Added

- Initial release.
