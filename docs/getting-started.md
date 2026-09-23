# Getting started

This walks you from `composer require` to a checkout your customers can use.

By the end, the cart and checkout are on your own URLs, styled to your brand, with your own copy, and you know which screen to open to change each part.

## 1. Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

Craft Commerce must already be installed with at least one payment gateway configured.

## 2. Set your paths

Open **Checkout -> General**. Set **Cart path** and **Checkout path** to where you want them, for example `shop/cart` and `shop/checkout`.

Visit the checkout path. You should see the contact step, or a redirect to the cart if the cart is empty. Add a product and try again.

## 3. Choose the layout

Open **Checkout -> Appearance**. **Checkout layout** switches between separate steps and one page. Multi-page is the default.

Reload the checkout. On single-page you see the **Contact information**, **Shipping address**, **Shipping method** and **Payment** panels together, with the order summary beside them. See [single-page checkout](./user-guide/single-page-checkout.md).

## 4. Brand it

Open **Checkout -> Appearance**. Set **Brand color**, **Header background color**, **Header text color**, **Font**, **Logo** and **Logo height**. **Component style** switches every component between **Rounded** and **Flat**.

Reload the checkout. The buttons and links use your brand color.

## 5. Write your copy

Open **Checkout -> Notes & Links**. Edit the note on each step, the newsletter label, the create account description, payment method notes and the footer links. See [notes and links](./user-guide/content.md).

Reload the checkout. Each step shows the note you wrote for it.

Buttons, field labels and messages are translations. To change one, copy its key from the plugin's `src/translations/en/foster-checkout.php` into your site's `translations/en/foster-checkout.php` with your wording.

## 6. Decide what a line item shows

Open **Checkout -> Line Items**. Turn the SKU on or off, hide options by prefix, cut long option values, and add rules that rewrite an option's name or value before a customer sees it. See [line items](./user-guide/line-items.md).

Reload the cart. Each line shows the SKU and options as you set them.

## 7. Add checkout fields

To collect a detail the checkout does not ask for, open **Checkout -> Custom Fields** and add it to one of the five positions. The field has to exist on the order first, under **Commerce -> Settings -> Order Fields**. See [checkout fields](./user-guide/checkout-fields.md).

Reload the checkout. The field shows at the position you chose.

## 8. Set up your gateways

Open **Checkout -> Gateways**. Every gateway configured in Commerce is listed. Open one to set the name customers see. On a Manual gateway, also add fields the customer fills in when they pick it. A Stripe gateway also sets how its payment element lists the payment methods, their order, and whether Link is included. A PayPal gateway also sets which funding sources its buttons offer, which card brands its card fields take, plus the locale and SDK components.

Reload the payment step. Each gateway shows under the name you set.

Place a test order to confirm payment works end to end.

## Where to go next

- [Settings reference](./reference/settings.md), every setting, its config key and its default
- [Permissions](./reference/permissions.md), what each permission grants
- [Plugin integrations](./reference/integrations.md), what changes when AvaTax, Gift Voucher, Postie and others are installed
- [Address fields](./user-guide/address-fields.md), what the checkout asks for on an address
- [Customer pickup](./user-guide/customer-pickup.md), letting a customer collect an order from the store location
- [Products that don’t require shipping](./user-guide/products-that-dont-require-shipping.md), skipping shipping for services and other unshippable products
- [Custom includes](./dev-guide/custom-includes.md), injecting your own templates into every cart and checkout page
- [Contributed fields](./dev-guide/contributed-fields.md), adding a checkout field your own code stores
