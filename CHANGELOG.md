# Release Notes for Foster Checkout

## Unreleased

### Added

- Show the stock count switch, under **Checkout -> Line Items**, for whether the cart says how many are left in stock.
- Show a third address line switch, under **Checkout -> General**.
- A name customers see, per gateway, under **Checkout -> Gateways**. Blank uses the gateway's own name.
- Let customers name a saved address switch, under **Checkout -> General**. The label shows only where a customer edits an address they already saved; Commerce names a new one.

### Changed

- Applying or removing a coupon on the single-page checkout restates the line item prices without reloading the page.

> {warning} Checkout address forms no longer offer a third address line. Turn on **Show a third address line** under **Checkout -> General** to keep it. Values already stored in the third line still print in formatted addresses.

## 1.0.0

- Initial release
