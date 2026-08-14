# Installation

A drop-in checkout for Craft Commerce, with its copy and settings managed from the control panel.

## Requirements

- Craft CMS `^5.0.0`
- Craft Commerce `^5.0.15`
- PHP `^8.3`

## Install

From the Plugin Store, search for "Foster Checkout" and press Install.

With Composer:

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

## Configure

Everything is set under **Checkout** in the control panel. No config file is required.

Start at **Checkout -> General** and set the paths the cart and checkout are served from:

- **Cart path**: site-relative path for the cart, for example `cart` or `shop/cart`. Default `cart`.
- **Checkout path**: site-relative path for the checkout steps. Default `checkout`.
- **Account path**: where completed checkout steps link to. Default `/`.
- **Cancel path**: where a customer goes if they cancel. Default `/`.

Then work through **Appearance** for branding, **Features** for the optional behaviours, and **Content** for the copy shown on each step.

Settings persist to project config, so they are editable only where `allowAdminChanges` is on. Content is stored in the plugin's own table and stays editable on production.

See [settings](./reference/settings.md) for every setting, and [permissions](./reference/permissions.md) for granting access.

## Optional config file

A site may ship `config/foster-checkout.php`. Copy the plugin's `src/config.php` as a starting point. Anything set there overrides the control panel, per top-level key.

## Upgrading an existing site

Sites that kept checkout copy in entry or global set fields, mapped through the `notes` and `links` config keys, have it migrated into the plugin's content storage on update. Run migrations as usual:

```sh
./craft migrate/all
```

Copy already entered in the control panel is left alone, so the migration is safe to run again. After it runs, remove `notes` and `links` from the config file. A gateway note defined as a PHP closure cannot be stored as content, so leave that one in place.
