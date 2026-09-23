# Release Notes for Foster Checkout

## Unreleased

### Added

- Added **Saved address limit** at **Checkout -> Addresses**, capping how many of a customer's saved addresses the checkout offers, most recently updated first and always including their primary address.
- Added **Klaviyo tracking** at **Checkout -> Features**, which decides whether the checkout reports to Klaviyo and offers the newsletter checkbox.
- Added **Phone field** at **Checkout -> Addresses**, naming the address field that holds a phone number so its input asks for a phone keypad.
- Added **Summary include** at **Checkout -> General**, a template given the cart and rendered in its own container above the checkout summary, on both checkout layouts. It is given the step it is rendering on, so a message can be shown on one step only.
- Added **Header text color** at **Checkout -> Appearance**, used for the cart link and for the store name a header without a logo falls back to.
- Added a cart link to the checkout header on the checkout steps.
- Added `craft.fostercheckout.klaviyoListId()`, the Klaviyo list ID with any environment variable resolved.
- Added `craft.fostercheckout.customerAddresses()`, the saved addresses the checkout offers a customer.
- Added `craft.fostercheckout.addressPostalCodeInputModes()`, the `inputmode` each country's postal code field takes.
- Added **Default country** at **Checkout -> Addresses**, the country a new address starts on.
- Added **Image size** and **Image fit** at **Checkout -> Line Items**, sizing the cart image and choosing whether it is shown whole or cropped to a square. Narrow screens use half the size.
- Added **Logo height** at **Checkout -> Appearance**.
- Added **Create account description** at **Checkout -> Notes & Links**, shown under the create account checkbox. Blank shows nothing.
- Added **Payment method layout** on a Stripe gateway at **Checkout -> Gateways**, choosing whether its payment element lists the payment methods as tabs, as an accordion, or however Stripe decides.
- Added **Include Link** on a Stripe gateway at **Checkout -> Gateways**, which decides whether Stripe's payment element offers Link and the payment methods marked “Powered by Link”.
- Added **Payment method order** on a Stripe gateway at **Checkout -> Gateways**, ranking the payment method types its payment element lists. A type Stripe is not offering is skipped, and one left out follows the ranked ones.
- Added **Hidden funding sources** and **Extra funding sources** on a PayPal gateway at **Checkout -> Gateways**, choosing which funding sources its buttons offer.
- Added **Turned away card brands** on a PayPal gateway at **Checkout -> Gateways**. PayPal has deprecated the option it writes, so it can stop working.
- Added **Locale** and **SDK components** on a PayPal gateway at **Checkout -> Gateways**.
- Added a “Did you mean …?” suggestion under the checkout email field for a mistyped domain, such as `gmial.com`.
- Added the newsletter checkbox for signed-in customers.

### Changed

- **Klaviyo tracking** is off on update, so a store already running Klaviyo turns it on for the checkout deliberately.
- Replaced the **Edit** link above the summary items with the cart link in the checkout header.
- Moved the contact step's sign-in link out of the panel heading and onto the email row, where it reads as the alternative to entering an email rather than as part of the heading.
- Renamed the **Content** screen to **Notes & Links**, and the **Fields** screen to **Custom Fields**.
- Moved **Placeholder images** and `options.imagerXConfig` from `options` to `lineItems`. A config file using the old keys still works.
- Moved **Checkout layout** and **Page transitions** to **Checkout -> Appearance**, **Verify shipping addresses** and the address suggestion settings to **Checkout -> Addresses**, **Offer pickup at the store location** and **Pickup label** to **Checkout -> Features**, **Zero value gateways** to **Checkout -> Gateways**, and **Customer order notes field** to **Checkout -> Custom Fields**.
- Editing address verification and address suggestions now needs **Manage settings** rather than **Manage features**, since they moved to the Addresses screen.
- Renamed `Checkout::lineItemImageField()` to `lineItemImageFields()`, which returns every configured image field for a product type, variant first, rather than only the first one. The old name still works, is deprecated, and is removed in the next major release.
- A line item with no variant image now falls back to the product image field, instead of showing no image.
- The delivery date label and message are read-only at **Checkout -> Features**, since no template in the plugin renders a delivery date.
- The voucher form accepts gift voucher codes only, and posts to `foster-checkout/voucher/add-code`. Gift Voucher's own action applies a Commerce coupon code entered there, replacing the order's coupon and still reporting the voucher as failed. A template overriding the payment step needs the new action.
- A panel now says an email address is needed before the checkout can save a guest's cart, instead of leaving the panel unchanged.
- The newsletter checkbox sits above the create account checkbox.
- The billing address form now opens on its own when an order needs no shipping and the customer has no saved addresses, instead of behind a **Use a different billing address** choice.
- When **Use the built-in cart template** is off, **Checkout -> Line Items** now hides the settings only that template uses. A hidden setting keeps its stored value.
- **Klaviyo list ID** at **Checkout -> Features** suggests environment variables and resolves one before rendering, the way Craft's own settings do. A config file no longer has to read the variable itself.
- The discount code heading no longer repeats the applied code, which the row beneath it already names.
- A coupon's name is left out where it matches the code itself.
- The Stripe payment element takes `layout.type` from **Payment method layout** and `wallets.link` from **Include Link**.
- The single page checkout no longer names a lone payment method, since there is no second one to choose between, and its form starts at the top of the panel.
- Payment method notes now show for every gateway, not only Manual gateways.
- **Checkout -> Gateways** offers a field layout on Manual gateways only, since only those render their fields at checkout.

### Removed

- Removed **Save for later** from **Checkout -> Line Items** and the cart, since its button had no action.
- Removed the checkout's login and register pages, and the **Account** note at **Checkout -> Notes & Links**. **Sign in** goes to Craft's `loginPath`. **Breaking**: a store linking to `<checkout>/login` or `<checkout>/register` links to its own pages instead.
- Removed the **Products** screen. **Preview image fields** moved to **Checkout -> Line Items**, and **Products that never ship**, now **Products that don't require shipping**, moved to **Checkout -> Addresses**.
- Removed **Extra parameters** and the `paymentGateways.<handle>.params` setting behind it. The keys merchants set there have their own settings on the Stripe and PayPal gateways, and `Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS` sets any other key with PHP types. A `params` key is now logged and ignored, whether it was stored from the removed table or named in a config file. **Breaking**: a store relying on it, such as one hiding a PayPal funding source, sets the matching gateway setting instead.

### Fixed

- Fixed a bug where saving an address the store cannot ship to reported “Unable to update cart.” with no reason.
- Fixed a bug where the checkout listed a customer's saved addresses oldest first.
- Fixed a bug where the postal code field opened a letter keyboard on a phone in countries whose postal codes are digits.
- Fixed a bug where the shipping method panel reported no shipping options before an address was entered.
- Fixed a bug where the single page checkout hid the shipping method panel before an address was entered, on a store that doesn't require a shipping method.
- Fixed a bug where the single page checkout reported “Unable to update cart.” when an email was entered while the new billing address form held only its preselected country.
- Fixed a bug where the checkout accepted an email address ending in a one-letter domain, such as `name@example.c`.
- Fixed a bug where a signed-in customer on the stepped checkout could not pay while an email step field was required.
- Fixed a bug where the stepped checkout's newsletter checkbox did not subscribe the customer.
- Fixed a bug where a line item image set to fill its box was scaled up from an image that kept its own shape.
- Fixed a bug where one setting pinned in a config file disabled the other settings in its group at **Checkout -> General**.
- Fixed a bug where **Test the connection** for address suggestions returned to the wrong screen and discarded the API key on screen.
- Fixed a JavaScript error on the payment step of the stepped checkout, where PayPal's script read its wrapper before the panel was rendered. The panel now renders the way the single page checkout already rendered it.
- Fixed the postal code keyboard never changing with the country, and asking for a numeric keypad in countries whose postal code holds a space or a hyphen, such as the United States.
- Fixed a bug where a store selling to one country reported an incomplete shipping address as soon as a guest entered their email, since the country a lone option preselects counted as an address worth saving.
- Fixed a bug where the shipping address saved without a required custom address field, such as a phone number, leaving Commerce to reject the cart.
- Fixed a bug where a coupon code a guest submitted before entering an email was discarded without a message.
- Fixed a bug where a panel kept its error mark after a later save had cleared the message.
- Fixed the shipping address not saving when only a field that does not change the shipping rate was edited, such as the street or the name.
- Fixed a bug where the address lookup's own settings were never validated, so a config file naming an unknown provider saved without complaint and left address suggestions unavailable.
- Fixed **Klaviyo list ID** rendering an environment variable name into the newsletter form rather than the list it names, so the signup reached Klaviyo with a list ID of `$KLAVIYO_LIST_ID`.
- Fixed the single page checkout offering one payment method rendering no payment form when the cart already totaled zero on load.
- Fixed the create account checkbox never switching on at the email step of the stepped checkout.
- Fixed a JavaScript error while a coupon was applied, where the checkout read the page it is mounted on from a watched value, which Alpine re-runs without it.
- Fixed the single page checkout letting Stripe ask for a phone number the address form had already collected, by passing the billing address phone to the payment element.

## 1.6.0 - 2026-09-14

### Added

- Added **Show the address label** at **Checkout -> Addresses**, which names a saved address by the label its customer gave it in the checkout's address choices and in the completed steps. An order's address borrows the name from the address it was copied from, and the store location is left out, since a customer never named it.
- Added `craft.fostercheckout.addressPreview()`, the one-line preview of an address as the checkout renders it.

### Fixed

- Fixed an error that occurred when a cart held a product that had since been moved to the trash.

## 1.5.0 - 2026-09-13

### Added

- Added a **Checkout -> Addresses** settings screen, which takes the priority countries, address field and address label settings from **General**.
- Added **Hidden billing address fields**, for address fields a store asks for on delivery but not on the address a card is billed to.
- Added **Required billing address fields**. **Required address fields** is now **Required shipping address fields** and applies to shipping addresses and saved address edits; an existing list is copied to the billing side on update.

### Changed

- The single-page checkout now re-reads the payment block after every save, so a reason the customer can fix on the page clears without a reload. The payment form mounts again when it clears.
- The multi-page payment step no longer repeats a “Payment method” heading under its “Payment” heading.

## 1.4.3 - 2026-09-12

### Fixed

- Fixed the single-page checkout not saving or re-quoting shipping when only a custom address field changed.
- Fixed a panel keeping an earlier save error after a later save succeeded.

## 1.4.2 - 2026-09-12

### Added

- Added a `Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS` event, so a module can change what a gateway's payment form is rendered with for one order.
- Added a “No shipping methods message” at **Checkout -> Content**, shown in the shipping method panel when nothing can be quoted.

### Changed

- A gateway's **Extra parameters** now merge into the Stripe payment form as well, not only PayPal's.

### Fixed

- Fixed stray import lines inside two event docblocks.

## 1.4.1 - 2026-09-12

### Added

- Added a `Checkout::EVENT_DEFINE_PAYMENT_BLOCK` event and `craft.fostercheckout.paymentBlock(order)`, so a module can hide the payment form and show a reason instead.

### Fixed

- Fixed the new shipping address form rendering below the “Customer pickup” choice on both checkouts.
- Fixed the single-page checkout's error and coupon messages sitting flush against the panels below them.

## 1.4.0 - 2026-09-12

### Added

- Added an “Offer pickup at the store location” switch and a “Pickup label” at **Checkout -> General** (`enableCustomerPickup` and `customerPickupLabel` in config), which list the Commerce store location as a shipping address choice at both checkouts.
- Added `craft.fostercheckout.isCustomerPickup(order)` for templates and modules, and a `customerPickup` flag in the cart response’s checkout state.

### Fixed

- Fixed an error that could occur when a saved address that no longer validates was chosen at the checkout.

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
