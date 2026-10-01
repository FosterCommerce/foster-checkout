# Release Notes for Foster Checkout

## Unreleased

### Added

- Added support for Small Pics. With it enabled, line item images use Craft's own transforms, which Small Pics serves, even when Imager X is installed.
- Added **Default country**, **Phone field**, **Saved address limit**, and **Saved address display** at **Checkout -> Addresses**, setting the country a new address starts on, which address field asks for a phone keypad, how many saved addresses the checkout offers, and whether the checkout offers them as a searchable dropdown.
- Added **Show the address label** at **Checkout -> Addresses**, which names a saved address by the label its customer gave it in the checkout's address choices and in the completed steps. An order's address borrows the name from the address it was copied from, and the store location is left out, since a customer never named it.
- Added **Klaviyo tracking** at **Checkout -> Features**, which decides whether the checkout reports to Klaviyo and offers the newsletter checkbox. It is off on update, so a store already running Klaviyo turns it on deliberately.
- Added **Summary include** at **Checkout -> General**, a template given the cart and rendered in its own container above the checkout summary, on both checkout layouts. It is given the step it is rendering on, so a message can be shown on one step only.
- Added **Header text color** and **Logo height** at **Checkout -> Appearance**.
- Added `craft.fostercheckout.klaviyoListId()`, `craft.fostercheckout.customerAddresses()`, `craft.fostercheckout.addressPostalCodeInputModes()`, `craft.fostercheckout.offersNewsletter()`, and `craft.fostercheckout.addressPreview()`.
- Added **Image size** and **Image fit** at **Checkout -> Line Items**, sizing the cart image and choosing whether it is shown whole or cropped to a square. Narrow screens use half the size.
- Added **Create account description** at **Checkout -> Notes & Links**, shown under the create account checkbox. Blank shows nothing.
- Added **Payment method layout**, **Payment method order**, and **Include Link** on a Stripe gateway at **Checkout -> Gateways**, choosing how its payment element lists payment methods, in what order, and whether it offers Link.
- Added **Hidden funding sources**, **Extra funding sources**, **Turned away card brands**, **Locale**, and **SDK components** on a PayPal gateway at **Checkout -> Gateways**. PayPal has deprecated the option **Turned away card brands** writes, so that setting can stop working.
- Added a “Did you mean …?” suggestion under the checkout email field for a mistyped domain, such as `gmial.com`.
- Added the newsletter checkbox for signed-in customers, in the contact panel or on the stepped checkout's email step.

### Changed

- Replaced the **Edit** link above the summary items with a cart link in the checkout header.
- Moved the contact step's sign-in link out of the panel heading and onto the email row, where it reads as the alternative to entering an email rather than as part of the heading.
- Renamed the **Content** screen to **Notes & Links** and the **Fields** screen to **Custom Fields**, and moved settings onto the screen for their area, such as address verification to **Checkout -> Addresses**.
- Moved **Placeholder images** and `options.imagerXConfig` from `options` to `lineItems`. A config file using the old keys still works.
- Editing address verification and address suggestions now needs **Manage settings** rather than **Manage features**, since they moved to the Addresses screen.
- Renamed `Checkout::lineItemImageField()` to `lineItemImageFields()`, which returns every configured image field for a product type, variant first.
- A line item with no variant image now falls back to the product image field, instead of showing no image.
- The voucher form now accepts gift voucher codes only, and posts to `foster-checkout/voucher/add-code`.
- A panel now says an email address is needed before the checkout can save a guest's cart, instead of leaving the panel unchanged.
- The newsletter checkbox now sits above the create account checkbox.
- **Return to cart** on the stepped checkout now sits below the steps, as on the single-page checkout, and shows on narrow screens.
- The billing address form now opens on its own when an order needs no shipping and the customer has no saved addresses, instead of behind a **Use a different billing address** choice.
- When **Use the built-in cart template** is off, **Checkout -> Line Items** now hides the settings only that template uses. A hidden setting keeps its stored value.
- **Klaviyo list ID** at **Checkout -> Features** suggests environment variables, the way Craft's own settings do.
- The discount code heading no longer repeats the applied code, and a coupon's name is left out where it matches the code.
- The single-page checkout no longer names a lone payment method, since there is no second one to choose between, and its form starts at the top of the panel.
- Payment method notes now show for every gateway, not only Manual gateways.
- **Checkout -> Gateways** offers a field layout on Manual gateways only, since only those render their fields at checkout.

### Removed

- Removed **Save for later** from **Checkout -> Line Items** and the cart, since its button had no action.
- Removed the checkout's login and register pages, and the **Account** note at **Checkout -> Notes & Links**. **Sign in** goes to Craft's `loginPath`.
- Removed the **Products** screen. **Preview image fields** moved to **Checkout -> Line Items**, and **Products that never ship**, now **Products that don't require shipping**, moved to **Checkout -> Addresses**.
- Removed **Extra parameters** and the `paymentGateways.<handle>.params` setting behind it. The keys merchants set there have their own settings on the Stripe and PayPal gateways, and `Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS` sets any other key with PHP types. A `params` key is now logged and ignored, whether it was stored from the removed table or named in a config file.

### Fixed

- Fixed an error that occurred when a cart held a product that had since been moved to the trash.
- Fixed a bug where **Return to cart** on the stepped checkout's billing step opened the checkout instead of the cart.
- Fixed a bug where choosing an option in a searchable dropdown, such as the country, left its text selected.
- Fixed a bug where the stepped checkout's payment step showed extra space above the payment methods on a store without Gift Voucher.
- Fixed a bug where saving an address the store cannot ship to reported “Unable to update cart.” with no reason.
- Fixed a bug where the checkout listed a customer's saved addresses oldest first.
- Fixed a bug where the postal code field opened the wrong keyboard on a phone for the chosen country.
- Fixed a bug where the shipping method panel reported no shipping options, or was hidden on the single-page checkout, before an address was entered.
- Fixed a bug where a country preselected on an empty address form counted as an address to save, so entering an email reported “Unable to update cart.” or an incomplete shipping address.
- Fixed a bug where the checkout accepted an email address ending in a one-letter domain, such as `name@example.c`.
- Fixed a bug where a signed-in customer on the stepped checkout could not pay while an email step field was required.
- Fixed a bug where the stepped checkout's newsletter checkbox did not subscribe the customer.
- Fixed a bug where a line item image set to fill its box was scaled up from an image that kept its own shape.
- Fixed a bug where one setting pinned in a config file disabled the other settings in its group at **Checkout -> General**.
- Fixed a bug where Stripe's payment element asked for a phone number the billing address already held.
- Fixed a bug where **Test the connection** for address suggestions returned to the wrong screen and discarded the API key on screen.
- Fixed a JavaScript error on the payment step of the stepped checkout, where PayPal's script read its wrapper before the panel was rendered. The panel now renders the way the single-page checkout already rendered it.
- Fixed a bug where the shipping address saved without a required custom address field, such as a phone number, leaving Commerce to reject the cart.
- Fixed a bug where a coupon code a guest submitted before entering an email was discarded without a message.
- Fixed a bug where a panel kept its error mark after a later save had cleared the message.
- Fixed the shipping address not saving when only a field that does not change the shipping rate was edited, such as the street or the name.
- Fixed a bug where the address lookup's own settings were never validated, so a config file naming an unknown provider saved without complaint and left address suggestions unavailable.
- Fixed **Klaviyo list ID** rendering an environment variable name into the newsletter form rather than the list it names, so the signup reached Klaviyo with a list ID of `$KLAVIYO_LIST_ID`.
- Fixed the single-page checkout offering one payment method rendering no payment form when the cart already totaled zero on load.
- Fixed the create account checkbox never switching on at the email step of the stepped checkout.
- Fixed a JavaScript error while a coupon was applied, where the checkout read the page it is mounted on from a watched value, which Alpine re-runs without it.

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
