![Foster Checkout Icon](resources/img/header.png)

# Foster Checkout

A **checkout** for Craft Commerce, with its copy and settings managed from the control panel.

## Overview

- Give your store a complete checkout (email, address, shipping, billing, payment and confirmation) at paths you choose, as separate steps or one page, with an optional cart to match.
- Edit checkout copy on production, per site or per language, without creating custom fields.
- Set branding, line items, gateways, addresses and features on control panel screens, or pin any setting in a `foster-checkout.php` config file.
- Ask customers for details the checkout does not collect (a purchase order number, for example) at five points in the flow.
- Skip the shipping address and method for services and other products that do not ship.
- Offer your store location as a pickup address.
- Catch mistyped addresses and email domains before the order is placed.

## Use Foster Checkout when

- The storefront needs a checkout, and has no templates for it yet. The cart can be the plugin's or your own.
- Checkout wording has to change on production, where admin changes are disabled.
- The store runs more than one site or language, and each needs its own checkout copy.
- The catalog mixes shippable products with services that need no shipping address.
- An order needs details Commerce does not collect, such as a purchase order number.

## Requirements

- Craft CMS `^5.0.0`
- Craft Commerce `^5.0.15`
- PHP `^8.3`

## Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

For the full guide, see [installation](./docs/installation.md).

## Cart and checkout

Once installed, the store has a checkout at the path you choose: contact, shipping address, shipping method, billing, payment and confirmation. Use the plugin's cart, or turn off **Use the built-in cart template** at **Checkout -> General** and keep your own. Set the brand color, font, logo and component style at **Checkout -> Appearance**.

See [getting started](./docs/getting-started.md).

## Single-page checkout

Run the checkout as one page instead of separate steps. The cart saves as the customer types. Every other setting applies to both layouts, so a store can switch without reconfiguring anything.

See [single-page checkout](./docs/user-guide/single-page-checkout.md).

## Notes and links

Edit the note on each step, payment method notes, the newsletter label and the footer links at **Checkout -> Notes & Links**. The copy stays editable on production, because the plugin stores it in its own database table instead of project config. On a multi-site install, copy varies per site or per language, following the content translation method.

Each note is rendered as a Twig template, so copy can reference the cart or the order. For the same reason, the **Edit checkout content** permission lets a user run code on the server.

See [notes and links](./docs/user-guide/content.md).

## Line items

Rules rewrite what a cart line shows for an option a customer chose, so a stored `giftWrap: 1` reads as `Gift wrap: Yes`. Each rule pairs a condition on the option's name or value with a replacement name, a replacement value, or both.

Values stay as the customer typed them unless a rule sets one. Long values can be cut short, and the SKU and stock count can each be hidden.

See [line items](./docs/user-guide/line-items.md).

## Checkout fields

Ask a customer for anything the checkout does not collect, such as a purchase order number, and choose where it appears. Five positions across the checkout each hold a field layout, and a field added to one is shown at that point. A layout field has to exist on the order first.

See [checkout fields](./docs/user-guide/checkout-fields.md).

## Address fields

Add your own fields to the address form, and choose which fields are hidden or required, starting from Craft's address field layout. Set the country a new address starts on and the countries listed first.

To catch a bad address before it reaches fulfillment, Avalara returns a corrected address the customer can accept, or Google Places and Loqate suggest addresses as the customer types. Each needs its own account and key.

See [address fields](./docs/user-guide/address-fields.md).

## Products that don’t require shipping

Mark services, training seats and other unshippable products with a product condition. A cart holding only those skips the shipping address and method on both layouts. Commerce and shipping plugins treat their variants as not shippable, so a mixed cart is rated on what ships.

See [products that don’t require shipping](./docs/user-guide/products-that-dont-require-shipping.md).

## Customer pickup

Offer the store location as a shipping address choice, so a customer can collect an order. A pickup order ships to your own address, which a free shipping method can match by zone.

See [customer pickup](./docs/user-guide/customer-pickup.md).

## Documentation

See [Foster Checkout on fostercommerce.com](https://www.fostercommerce.com/craft-cms-plugins/foster-checkout).

## License

Proprietary.

---

<a href="https://www.fostercommerce.com" target="_blank"><img src="./resources/img/foster-commerce.svg" alt="Foster Commerce" width="160" height="40"></a>
