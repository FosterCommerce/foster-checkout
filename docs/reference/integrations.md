# Plugin integrations

Six plugins change the checkout when installed. None is required, and none is bundled. Each is detected at runtime, so installing one is all it takes.

| Plugin | Package | What it adds |
| --- | --- | --- |
| AvaTax | `surprisehighway/craft-avatax` | Address verification on the shipping address |
| Gift Voucher | `verbb/gift-voucher` | A voucher and gift card field on the payment step |
| Klaviyo Connect Plus | `fostercommerce/klaviyo-connect-plus` | The newsletter checkbox on the contact step |
| Postie | `verbb/postie` | Carrier shipping rates |
| Imager X | `spacecatninja/imager-x` | Line item image transforms |
| Advanced Discounts | `fostercommerce/advanced-discounts` | Coupon names and messages in the cart and at checkout |

## AvaTax

Needed for **Verify shipping addresses** on **Checkout -> Features**. AvaTax's own **Enable address validation** setting must also be on. Covers the United States and Canada. See [settings](./settings.md#address-suggestions) for how it interacts with address suggestions.

## Gift Voucher

Adds a code field to the payment step and lists applied vouchers in the order summary. The plugin reads the code from Gift Voucher's stored snapshot rather than from the adjustment description, because that description is translated.

## Klaviyo Connect Plus

The newsletter checkbox needs this plugin **and** a list ID at **Checkout -> Features -> Klaviyo list ID**. Without both, the checkbox is not shown. Its label is edited at **Checkout -> Content**.

Events are not sent when the signed-in user's email is not the order's, since they would be filed under someone who is not the shopper. See [checkout for another customer](../dev-guide/checkout-for-another-customer.md#whether-klaviyo-is-tracking).

## Postie

Postie's rates appear as shipping methods. The plugin registers the checkout path with Postie at runtime, the one page or the multi-page shipping step, so rates are fetched where the customer picks a method.

Variants matched by [Products that never ship](../user-guide/products-that-never-ship.md) are left out of Postie's parcel. Postie still quotes carriers for a [customer pickup](../user-guide/customer-pickup.md) order, since the store location is an address like any other; a module can leave them off; see [detecting a pickup order](../dev-guide/customer-pickup.md).

## Imager X

Line item images in the cart and checkout are transformed through Imager X when it is installed. Configure the transform with the `options.imagerXConfig` setting in `config/foster-checkout.php`. Without the plugin, images are used as they are.

## Advanced Discounts

An applied Advanced Discounts coupon is named next to its code, as a Commerce coupon is. Messages from Advanced Discounts, including why a coupon did not apply, are rendered in the cart and at checkout. On the single-page checkout they update as a coupon is applied or removed.
