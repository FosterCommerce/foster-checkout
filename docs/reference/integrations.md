# Plugin integrations

These plugins change the checkout when installed. Each is optional and installed separately. AvaTax and Klaviyo Connect also need settings turned on.

| Plugin | Package | What it adds |
| --- | --- | --- |
| AvaTax | `surprisehighway/craft-avatax` | Address verification on the shipping address |
| Blitz | `putyourlightson/craft-blitz` | Cart and checkout pages kept out of its cache |
| Gift Voucher | `verbb/gift-voucher` | A voucher and gift card field on the payment step |
| Klaviyo Connect | `fostercommerce/klaviyoconnect` 7.3.0 or later | Newsletter checkbox subscriptions to a Klaviyo list |
| Postie | `verbb/postie` | Carrier shipping rates |
| Stripe for Craft Commerce | `craftcms/commerce-stripe` | Stripe's payment element, with settings at **Checkout -> Gateways** for its layout, payment method order and Link |
| PayPal Checkout for Craft Commerce | `craftcms/commerce-paypal-checkout` | PayPal buttons and card fields, with settings at **Checkout -> Gateways** for funding sources, card brands, locale and SDK components |
| Authorize.net for Craft Commerce | `digital-pros/commerce-authorize` | Card payments through Authorize.net |
| Small Pics | `smallpics/craft-smallpics` | Line item images transformed by Small Pics, with params such as a watermark |
| Imager X | `spacecatninja/imager-x` | Line item image transforms, with params such as a watermark |
| Advanced Discounts | `fostercommerce/advanced-discounts` | Coupon names and messages in the cart and at checkout |

## AvaTax

Needed for **Verify shipping addresses** on **Checkout -> Addresses**. AvaTax's own **Enable address validation** setting must also be on. Covers the United States and Canada. For how it interacts with address suggestions, see [address fields](../user-guide/address-fields.md#address-verification-and-suggestions).

## Blitz

Foster Checkout keeps the cart path and every checkout page, including the order confirmation page, out of Blitz's cache, so they don't need to be in Blitz's excluded URI patterns. This includes a cart template of your own at the cart path.

If your web server serves Blitz's cached files directly, it keeps serving the cart and checkout pages Blitz cached before you installed or updated Foster Checkout. To clear them, run:

```sh
./craft blitz/cache/clear-urls "https://example.com/cart*,https://example.com/checkout*"
```

## Gift Voucher

Adds a code field to the payment step and lists applied vouchers in the order summary.

## Klaviyo Connect

With **Newsletter checkbox** turned on at **Checkout -> Features** and a Klaviyo list ID at **Checkout -> Features -> Newsletter list ID**, the newsletter checkbox subscribes customers to that list.

The checkout ignores an earlier version. For when the checkbox is shown, or to use another newsletter service, see [newsletter services](../dev-guide/newsletter-services.md).

## Postie

Postie's rates appear as shipping methods. The plugin registers the checkout path with Postie at runtime, the one page or the multi-page shipping step, so rates are fetched where the customer picks a method.

Variants matched by [Products that don’t require shipping](../user-guide/products-that-dont-require-shipping.md) are left out of Postie's parcel. For how Postie quotes a pickup order, see [customer pickup](../user-guide/customer-pickup.md#pricing-pickup).

## Small Pics

With **Image transformer** set to Small Pics at **Checkout -> Line Items**, Small Pics transforms line item images in the cart and at checkout. The plugin adds the params in **Image transform params** to each image. Use them for a watermark or any other [Small Pics option](https://github.com/smallpics/craft-smallpics).

The plugin sets each image's width, height and mode. A Small Pics `fit` param replaces **Image fit**. For params set in both the control panel and the config file, see [what overrides what](./settings.md#what-overrides-what).

With **Image transformer** set to Craft, or to Auto (the default) while Small Pics is enabled, line item images use Craft's image transforms. Small Pics serves those when its `transformNativeImages` setting is on, with its `nativeTransformsParams` and not **Image transform params**.

## Imager X

Imager X transforms line item images when **Image transformer** is set to Imager X, or to Auto while Imager X is enabled and Small Pics is not. The plugin passes **Image transform params** as Imager X's transform defaults. Set a nested param, such as `transformerParams`, in `lineItems.imageTransformConfig` in `config/foster-checkout.php`, because the control panel table takes only text values.

With Imager X's `transformer` setting at `smallpics`, from `smallpics/imagerx-smallpics`, set a Small Pics watermark in `transformerParams`. A `width` or `fit` in `transformerParams` replaces the one Foster Checkout sets. The example works only with that transformer.

```php
'lineItems' => [
	'imageTransformer' => 'imager-x',
	'imageTransformConfig' => [
		'transformerParams' => [
			'watermarkPath' => '/assets/images/watermark.png',
		],
	],
],
```

`lineItems.imagerXConfig` is now `lineItems.imageTransformConfig`. See [upgrading](../upgrade.md#imager-x-transform-config-renamed).

## Advanced Discounts

An applied Advanced Discounts coupon is named next to its code, as a Commerce coupon is. Messages from Advanced Discounts, including why a coupon did not apply, are rendered in the cart and at checkout. On the single-page checkout they update as a coupon is applied or removed.
