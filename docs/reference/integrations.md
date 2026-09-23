# Plugin integrations

These plugins change the checkout when installed. Each is optional and installed separately. AvaTax and Klaviyo Connect Plus also need settings turned on.

| Plugin | Package | What it adds |
| --- | --- | --- |
| AvaTax | `surprisehighway/craft-avatax` | Address verification on the shipping address |
| Gift Voucher | `verbb/gift-voucher` | A voucher and gift card field on the payment step |
| Klaviyo Connect Plus | `fostercommerce/klaviyo-connect-plus` | The newsletter checkbox, in the contact panel or on the checkout's first step, and a Started Checkout event from the cart page's **Checkout** button |
| Postie | `verbb/postie` | Carrier shipping rates |
| Imager X | `spacecatninja/imager-x` | Line item image transforms |
| Advanced Discounts | `fostercommerce/advanced-discounts` | Coupon names and messages in the cart and at checkout |

## AvaTax

Needed for **Verify shipping addresses** on **Checkout -> Addresses**. AvaTax's own **Enable address validation** setting must also be on. Covers the United States and Canada. For how it interacts with address suggestions, see [address fields](../user-guide/address-fields.md#address-verification-and-suggestions).

## Gift Voucher

Adds a code field to the payment step and lists applied vouchers in the order summary.

## Klaviyo Connect Plus

The newsletter checkbox needs this plugin, **Klaviyo tracking** turned on at **Checkout -> Features**, and a list ID at **Checkout -> Features -> Klaviyo list ID**, which takes a list ID or an environment variable name such as `$KLAVIYO_LIST_ID`. Without all three, or with the label blank, the checkbox is not shown. Klaviyo tracking is off by default. The checkbox's label is edited at **Checkout -> Notes & Links**.

Events are not sent when the signed-in user's email is not the order's, since they would be filed under someone who is not the shopper. See [checkout for another customer](../dev-guide/checkout-for-another-customer.md#whether-klaviyo-is-tracking).

## Postie

Postie's rates appear as shipping methods. The plugin registers the checkout path with Postie at runtime, the one page or the multi-page shipping step, so rates are fetched where the customer picks a method.

Variants matched by [Products that don’t require shipping](../user-guide/products-that-dont-require-shipping.md) are left out of Postie's parcel. For how Postie quotes a pickup order, see [customer pickup](../user-guide/customer-pickup.md#pricing-pickup).

## Imager X

Line item images in the cart and checkout are transformed through Imager X when it is installed. Configure the transform with the `lineItems.imagerXConfig` setting in `config/foster-checkout.php`. Without the plugin, Craft's image transforms size the images.

## Advanced Discounts

An applied Advanced Discounts coupon is named next to its code, as a Commerce coupon is. Messages from Advanced Discounts, including why a coupon did not apply, are rendered in the cart and at checkout. On the single-page checkout they update as a coupon is applied or removed.
