# Installation

A checkout for Craft Commerce, with its copy and settings managed from the control panel.

## Requirements

- Craft CMS `^5.0.0`
- Craft Commerce `^5.0.15`
- PHP `^8.3`

## Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

## Configure

Most settings are under **Checkout** in the control panel. No config file is required.

Start at **Checkout -> General** and set the paths the cart and checkout are served from:

- **Cart path**: site-relative path for the cart, for example `cart` or `shop/cart`. Default `cart`.
- **Use the built-in cart template**: serves the plugin's cart at the cart path. Turn it off to serve your own template there. On by default.
- **Checkout path**: site-relative path for the checkout steps. Default `checkout`.
- **Account path**: where **View my account** links for a signed-in customer. Default `/`.
- **Cancel path**: where **Continue shopping** on the cart links. Default `/`.

Then work through **Appearance** for branding, **Features** for the optional behaviors, **Line Items** for how cart lines are shown, and **Notes & Links** for the copy shown on each step.

For every setting, see [settings](./reference/settings.md). For who can edit them, and on which environments, see [permissions](./reference/permissions.md).

## Optional config file

To set values in code, copy the plugin's `src/config.php` to `config/foster-checkout.php`. For which keys override the control panel, see [what overrides what](./reference/settings.md#what-overrides-what).

## Upgrading

To upgrade from an earlier version, see [upgrading](./upgrade.md).
