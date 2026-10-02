# Plugin integrations

These plugins change the checkout when installed. Each is optional and installed separately. AvaTax and Klaviyo Connect also need settings turned on.

| Plugin | Package | What it adds |
| --- | --- | --- |
| AvaTax | `surprisehighway/craft-avatax` | Address verification on the shipping address |
| Gift Voucher | `verbb/gift-voucher` | A voucher and gift card field on the payment step |
| Klaviyo Connect | `fostercommerce/klaviyoconnect` 7.3.0 or later | Newsletter checkbox subscriptions to a Klaviyo list |
| Postie | `verbb/postie` | Carrier shipping rates |
| Stripe for Craft Commerce | `craftcms/commerce-stripe` | Stripe's payment element, with settings at **Checkout -> Gateways** for its layout, payment method order and Link |
| PayPal Checkout for Craft Commerce | `craftcms/commerce-paypal-checkout` | PayPal buttons and card fields, with settings at **Checkout -> Gateways** for funding sources, card brands, locale and SDK components |
| Authorize.net for Craft Commerce | `digital-pros/commerce-authorize` | Card payments through Authorize.net |
| Small Pics | `smallpics/craft-smallpics` | Line item images served by Small Pics |
| Imager X | `spacecatninja/imager-x` | Line item image transforms |
| Advanced Discounts | `fostercommerce/advanced-discounts` | Coupon names and messages in the cart and at checkout |

## AvaTax

Needed for **Verify shipping addresses** on **Checkout -> Addresses**. AvaTax's own **Enable address validation** setting must also be on. Covers the United States and Canada. For how it interacts with address suggestions, see [address fields](../user-guide/address-fields.md#address-verification-and-suggestions).

## Gift Voucher

Adds a code field to the payment step and lists applied vouchers in the order summary.

## Klaviyo Connect

With **Newsletter checkbox** turned on at **Checkout -> Features** and a Klaviyo list ID at **Checkout -> Features -> Newsletter list ID**, the newsletter checkbox subscribes customers to that list.

The checkout ignores an earlier version. For when the checkbox is shown, or to use another newsletter service, see [newsletter services](../dev-guide/newsletter-services.md).

## Postie

Postie's rates appear as shipping methods. The plugin registers the checkout path with Postie at runtime, the one page or the multi-page shipping step, so rates are fetched where the customer picks a method.

Variants matched by [Products that don’t require shipping](../user-guide/products-that-dont-require-shipping.md) are left out of Postie's parcel. For how Postie quotes a pickup order, see [customer pickup](../user-guide/customer-pickup.md#pricing-pickup).

## Small Pics

With Small Pics enabled, line item images in the cart and checkout use Craft's own image transforms, which Small Pics serves when its `transformNativeImages` setting is on. This holds even when Imager X is installed.

## Imager X

Line item images in the cart and checkout are transformed through Imager X when it is installed and Small Pics is not. Configure the transform with the `lineItems.imagerXConfig` setting in `config/foster-checkout.php`. Without the plugin, Craft's image transforms size the images.

## Advanced Discounts

An applied Advanced Discounts coupon is named next to its code, as a Commerce coupon is. Messages from Advanced Discounts, including why a coupon did not apply, are rendered in the cart and at checkout. On the single-page checkout they update as a coupon is applied or removed.
