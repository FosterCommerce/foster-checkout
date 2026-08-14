![Foster Checkout Icon](resources/img/header.png)

# Foster Checkout

A drop-in **checkout** for Craft Commerce, with its copy and settings managed from the control panel.

## Overview

- Ships a complete cart and checkout flow (email, address, shipping, billing, payment, confirmation) at paths you choose.
- Lets store admins edit checkout copy on production, with no custom fields to create and map, and no developer involvement.
- Keeps copy per site or per language on multi-site installs, so each storefront reads its own wording.
- Puts branding, feature switches, product image fields, payment gateway fields and paths on control panel screens, so a store can run without a config file.
- Still accepts a `foster-checkout.php` config file, which overrides whatever is set in the control panel.
- Adds extra fields to a payment method (an account number, for example) and shows a note when a customer selects it.

## Requirements

- Craft CMS `^5.0.0`
- Craft Commerce `^5.0.15`
- PHP `^8.3`

## Install

```sh
composer require fostercommerce/craft-foster-checkout
./craft plugin/install foster-checkout
```

See [`docs/installation.md`](./docs/installation.md) for the full guide.

## Content

Checkout copy lives in the plugin's own database table rather than project config, so it stays editable on production where admin changes are disabled. Admins edit it at **Checkout -> Content**: notes for each step, payment method notes, and the footer links shown across the cart and checkout.

Each note is rendered as a Twig template, so copy can reference the cart or the order. On a multi-site install, copy varies per site or per language depending on the content translation method.

See [`docs/user-guide/content.md`](./docs/user-guide/content.md).

## Settings

Appearance, features, products, payment gateways and paths each get a control panel screen under **Checkout**. Anything a site sets in `config/foster-checkout.php` wins over the control panel, and those fields are shown as read-only so it is clear why an edit will not take.

See [`docs/reference/settings.md`](./docs/reference/settings.md).

## Permissions

- `foster-checkout-viewContent`: view the Content screen.
- `foster-checkout-editContent`: edit checkout copy. Copy is rendered as Twig, so this grants server-side code execution.
- `foster-checkout-manageAppearance`: edit branding.
- `foster-checkout-manageFeatures`: edit feature switches.
- `foster-checkout-manageSettings`: edit products, payment gateways and general settings.

Craft's own `accessPlugin-foster-checkout` is required in addition to any of the above.

See [`docs/reference/permissions.md`](./docs/reference/permissions.md).

## License

Proprietary.

## Documentation

See [`docs/index.md`](./docs/index.md).

Brought to you by [Foster Commerce](https://fostercommerce.com).
