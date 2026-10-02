![Foster Checkout](resources/img/header.png)

# Foster Checkout

A ready-made, best-practices cart and checkout for Craft Commerce.

## Overview

- Give your store a complete checkout (email, address, shipping, billing, payment, and confirmation) at paths you choose, as separate steps or one page, with an optional cart to match.
- Match the checkout to your brand with your own color, font, logo, and component style.
- Edit checkout notes and links on production, per site or per language, without creating custom fields.
- Ask for the details your orders need (a purchase order number, for example), and place each field on the step where it fits.
- Skip the shipping address for products that don't ship, and offer your store location as a pickup address.
- Catch mistyped email domains, and mistyped addresses with AvaTax, before the customer places the order.
- Sign customers up for your newsletter through Klaviyo Connect or the email marketing service you already use.

## How it works

Install the plugin and the store has a checkout at the path you choose. Configure the checkout on the **Checkout** screens in the control panel, or pin settings in a `config/foster-checkout.php` file. The plugin stores checkout copy in its own database table rather than project config, so the copy stays editable on production.

## Requirements

- Craft CMS `^5.0.0`
- Craft Commerce `^5.0.15`
- PHP `^8.3`

## Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

## Documentation

- [Getting started](https://www.fostercommerce.com/craft-cms-plugins/foster-checkout/docs/getting-started), from install to a checkout your customers can use
- [Single-page checkout](https://www.fostercommerce.com/craft-cms-plugins/foster-checkout/docs/user-guide/single-page-checkout), running the checkout as one page
- [Settings](https://www.fostercommerce.com/craft-cms-plugins/foster-checkout/docs/reference/settings), every setting, its default and what overrides what
- [Plugin integrations](https://www.fostercommerce.com/craft-cms-plugins/foster-checkout/docs/reference/integrations), what AvaTax, Gift Voucher, Klaviyo Connect, Postie, and others add

## License

Proprietary

---

<a href="https://www.fostercommerce.com" target="_blank"><img src="./resources/img/foster-commerce.svg" alt="Foster Commerce" width="160" height="40"></a>
