# Getting started

This walks you from `composer require` to a checkout your customers can use, in about twenty minutes.

By the end you will have the cart and checkout on your own URLs, styled to your brand, with your own copy, and you will know which screen to open when you want to change something.

## 1. Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

Craft Commerce must already be installed with at least one payment gateway configured.

## 2. Set your paths

Open **Checkout -> General**. Set the cart and checkout paths to where you want them, for example `shop/cart` and `shop/checkout`.

Visit the checkout path. You should see the contact step, or a redirect to the cart if the cart is empty. Add a product and try again.

## 3. Choose the layout

Open **Checkout -> Features**. **Checkout layout** switches between separate steps and one page. Multi-page is the default.

Reload the checkout. On single-page you will see contact, delivery, shipping and payment together, with the summary beside them. See [single-page checkout](./user-guide/single-page-checkout.md).

## 4. Brand it

Open **Checkout -> Appearance**. Set the brand color, header background, logo path and Google font. Corner style switches every component between rounded and square.

Reload the checkout. The buttons and links now use your brand color.

## 5. Write your copy

Open **Checkout -> Content**. Every piece of text in the checkout is here: the note on each step, the newsletter label, payment method notes and the footer links.

This is stored in the database rather than in project config, so it stays editable on production. See [checkout content](./user-guide/content.md).

## 6. Decide what a line item shows

Open **Checkout -> Line Items**. Turn the SKU on or off, hide options by prefix, cut long option values, and add rules that rewrite an option's name or value before a customer sees it. See [line items](./user-guide/line-items.md).

## 7. Ask for anything extra

If you need something the checkout does not collect, open **Checkout -> Fields** and add it to one of the five positions. The field has to exist on the order first, under **Commerce -> Settings -> Order Fields**. See [checkout fields](./user-guide/checkout-fields.md).

## 8. Set up your gateways

Open **Checkout -> Gateways**. Every gateway configured in Commerce is listed. Open one to add fields the customer fills in when they pick it, and any extra parameters the gateway needs.

Place a test order to confirm payment works end to end.

## Where to go next

- [Settings reference](./reference/settings.md), every setting, its config key and its default
- [Permissions](./reference/permissions.md), what each permission grants
- [Plugin integrations](./reference/integrations.md), what changes when AvaTax, Gift Voucher, Postie and others are installed
- [Custom includes](./dev-guide/custom-includes.md), injecting your own templates into every cart and checkout page
