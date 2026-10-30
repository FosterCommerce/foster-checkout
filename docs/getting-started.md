# Getting started

A ready-made, best-practices cart and checkout for Craft Commerce.

This walks you from `composer require` to a checkout your customers can use.

By the end, the cart and checkout run at your own URLs, with your brand and your own copy. You also know which screen to open to change each part.

## 1. Check the requirements

- Craft CMS `^5.10.0`
- Craft Commerce `^5.0.15`, installed with at least one payment gateway configured
- PHP `^8.3`

## 2. Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

## 3. Set your paths

Open **Checkout -> General**. Set **Cart path** and **Checkout path** to the paths you want, for example `shop/cart` and `shop/checkout`. To serve your own cart template at the cart path, turn off **Use the built-in cart template**.

Visit the checkout path. You see the contact step. If the cart is empty, the checkout redirects to the cart instead; add a product and visit the checkout path again.

No config file is required. To set values in code, copy the plugin's `src/config.php` to `config/foster-checkout.php`. For which value applies when both are set, see [what overrides what](./reference/settings.md#what-overrides-what).

## 4. Choose the layout

Open **Checkout -> Appearance**. **Checkout layout** switches between **Multi-page** (separate steps, the default) and **Single-page**.

Reload the checkout. With **Single-page**, you see the **Contact information**, **Shipping address**, **Shipping method** and **Payment** panels together, with the order summary beside them. See [single-page checkout](./user-guide/single-page-checkout.md).

## 5. Brand the checkout

Open **Checkout -> Appearance**. Set **Brand color**, **Header background color**, **Header text color**, **Font**, **Logo** and **Logo height**. **Component style** switches every component between **Rounded** and **Flat**.

Reload the checkout. The buttons and links use your brand color.

## 6. Write your copy

Open **Checkout -> Notes & Links**. Edit the note on each step, the newsletter checkbox label, the create account description, payment method notes and the footer links. See [notes and links](./user-guide/content.md).

Reload the checkout. Each step shows the note you wrote for it.

Button text, field labels and messages come from the plugin's translation file. To change one, copy its key from the plugin's `src/translations/en/foster-checkout.php` into your site's `translations/en/foster-checkout.php` with your wording.

## 7. Decide what a line item shows

Open **Checkout -> Line Items**. Turn the SKU on or off, hide options by prefix, shorten long option values, and add rules that rewrite an option's name or value before a customer sees it. See [line items](./user-guide/line-items.md).

Reload the cart. Each line shows the SKU and options as you set them.

## 8. Add checkout fields

To collect a detail the checkout does not ask for, first add the field to the order under **Commerce -> Settings -> Order Fields**. Then open **Checkout -> Custom Fields** and add the field to one of the five positions. See [checkout fields](./user-guide/checkout-fields.md).

Reload the checkout. The field shows at the position you chose.

## 9. Set up your gateways

Open **Checkout -> Gateways**. The screen lists every gateway configured in Commerce. Open one to set the name customers see. On a Manual gateway, also add fields the customer fills in when they pick it. For a Stripe gateway, you also set how its payment element lists payment methods, their order and whether it includes Link. For a PayPal gateway, you also set which funding sources its buttons show, which card brands its card fields accept, the locale and the SDK components.

Reload the payment step. Each gateway shows under the name you set.

If a gateway's page at **Commerce -> Settings -> Gateways** shows a **Webhook URL**, add that URL to the gateway account's webhook settings. If a customer pays but doesn't return to the checkout, the webhook lets Commerce complete the order.

Place a test order to confirm payment works end to end.

## Where to go next

- [Settings](./reference/settings.md), every setting, its default and what overrides what
- [Permissions](./reference/permissions.md), what each permission grants
- [Plugin integrations](./reference/integrations.md), what AvaTax, Gift Voucher, Klaviyo Connect, Postie, and others add
- [Address fields](./user-guide/address-fields.md), what the checkout asks for on an address
- [Customer pickup](./user-guide/customer-pickup.md), listing the store location as a shipping address
- [Products that don’t require shipping](./user-guide/products-that-dont-require-shipping.md), skipping shipping for services and other unshippable products
- [Custom includes](./dev-guide/custom-includes.md), injecting your own templates into every cart and checkout page
- [Contributed fields](./dev-guide/contributed-fields.md), adding a checkout field your own code stores
