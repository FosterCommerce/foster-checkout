# Plugin integrations

Six plugins change the checkout when installed. None is required, and none is bundled. Each is detected at runtime, so installing one is all it takes.

| Plugin | Package | What it adds |
| --- | --- | --- |
| AvaTax | `surprisehighway/craft-avatax` | Address verification on the shipping address |
| Gift Voucher | `verbb/gift-voucher` | A voucher and gift card field on the payment step |
| Klaviyo Connect Plus | `fostercommerce/klaviyo-connect-plus` | The newsletter checkbox on the contact step |
| Postie | `verbb/postie` | Carrier shipping rates |
| Imager X | `spacecatninja/imager-x` | Line item image transforms |
| Advanced Discounts | `fostercommerce/craft-advanced-discounts` | Coupon messages in the cart |

## AvaTax

Needed for **Verify shipping addresses** on **Checkout -> Features**. AvaTax's own **Enable address validation** setting must also be on. Covers the United States and Canada. See [settings](./settings.md#address-suggestions) for how it interacts with address suggestions.

## Gift Voucher

Adds a code field to the payment step and lists applied vouchers in the order summary. The plugin reads the code from Gift Voucher's stored snapshot rather than from the adjustment description, because that description is translated.

## Klaviyo Connect Plus

The newsletter checkbox needs this plugin **and** a list ID at **Checkout -> Features -> Klaviyo list ID**. Without both, the checkbox is not shown. Its label is edited at **Checkout -> Content**.

## Postie

Postie's rates appear as shipping methods. On single-page checkout the plugin registers the checkout path with Postie at runtime, so rates are fetched there as well.

## Imager X

Line item images in the cart and checkout are transformed through Imager X when it is installed. Configure the transform with the `options.imagerXConfig` setting in `config/foster-checkout.php`. Without the plugin, images are used as they are.

## Advanced Discounts

Coupon messages from Advanced Discounts are rendered in the cart, including why a coupon did not apply.
