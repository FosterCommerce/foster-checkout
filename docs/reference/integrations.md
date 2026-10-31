# Plugin integrations

Foster Checkout changes the checkout when one of these plugins is installed. AvaTax and Klaviyo Connect also need settings turned on.

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
| Mollie for Craft Commerce | `craftcms/commerce-mollie` | The Mollie account's payment methods, with their logos |
| Small Pics | `smallpics/craft-smallpics` | Line item images transformed by Small Pics, with params such as a watermark |
| Imager X | `spacecatninja/imager-x` | Line item image transforms, with params such as a watermark |
| Advanced Discounts | `fostercommerce/advanced-discounts` | Coupon names and messages in the cart and at checkout |

## AvaTax

**Verify shipping addresses** at **Checkout -> Addresses** needs AvaTax, with AvaTax's **Enable address validation** setting turned on. Verification covers addresses in the United States and Canada. For how it interacts with address suggestions, see [address fields](../user-guide/address-fields.md#address-verification-and-suggestions).

## Blitz

Foster Checkout keeps the cart path and every checkout page, including the order confirmation page, out of Blitz's cache. You do not need to add them to Blitz's excluded URI patterns. The cart path stays out of the cache when your own cart template renders it.

If your web server serves Blitz's cached files directly, it keeps serving the cart and checkout pages Blitz cached before you installed or updated Foster Checkout. To clear those cached pages, run:

```sh
./craft blitz/cache/clear-urls "https://example.com/cart*,https://example.com/checkout*"
```

## Gift Voucher

Foster Checkout adds a code field to the payment step and lists applied vouchers in the order summary.

### Checkout holds

From Gift Voucher 4.0.20, each payment places a hold on the voucher value it uses. The hold stays until the order completes, the payment is declined, or Foster Checkout or staff release it. Stripe's payment element starts a payment when it loads, so opening the payment step places a hold.

Foster Checkout releases holds when a customer applies a code, when staff apply one from **Manage gift vouchers** on an order's edit page, and before each payment:

| Hold | When Foster Checkout releases it |
| --- | --- |
| The order's own hold from an earlier payment attempt | Once the attempt has a transaction, or 15 minutes after the hold was placed |
| Another checkout's hold on the same code | After that checkout goes 15 minutes without starting a payment |
| The hold of an order that is complete, or has a pending, processing or successful payment | Never |
| A hold the **Checkout Holds** list shows as **Needs review** | Never |

A payment waiting on the customer, such as a 3DS check, does not keep its hold. Stripe's payment element starts that kind of payment whenever it loads, so counting it would keep the hold of every abandoned checkout. A customer who spends more than 15 minutes on that step can lose the hold to another checkout using the same code.

If another checkout's hold leaves the code with no value for this order, the code field on the payment step shows “This code is in use in another checkout. Try again in 5 minutes.” The number is the minutes left until that hold is 15 minutes old.

To release a hold whose payment can no longer succeed, open the code at **Gift Voucher -> Voucher Codes** and click **Release** under **Checkout Holds**.

## Klaviyo Connect

With **Newsletter checkbox** turned on at **Checkout -> Features** and a Klaviyo list ID in **Newsletter list ID**, the checkout subscribes customers who select the newsletter checkbox to that list.

The checkout ignores Klaviyo Connect versions before 7.3.0. For when the checkout shows the checkbox, or to use another newsletter service, see [newsletter services](../dev-guide/newsletter-services.md).

## Postie

Foster Checkout lists Postie's rates as shipping methods. It registers the checkout path with Postie at runtime, on the single-page checkout or the stepped checkout's shipping step, so Postie fetches rates where the customer picks a method.

Foster Checkout leaves variants matched by [Products that don’t require shipping](../user-guide/products-that-dont-require-shipping.md) out of Postie's parcel. For how Postie quotes a pickup order, see [customer pickup](../user-guide/customer-pickup.md#pricing-pickup).

## Mollie

For a Mollie gateway, the checkout lists the payment methods active on the Mollie account, with their logos, and selects the first. It lists only methods that accept the order's amount due, currency and billing country. When the customer selects a method with issuers, such as gift cards, the checkout also shows a **Bank or card** list. If the customer leaves that list at **Choose on the next page**, Mollie asks for the issuer after Commerce sends the customer to Mollie to pay.

Mollie translates and orders the methods for the site's locale. For a site whose region Mollie doesn't list, such as `en-AU`, the checkout requests the language's default region, such as `en_US`. For a language Mollie doesn't list, the request has no locale.

When the list is empty, the checkout shows the pay button without a method list, and the customer chooses a method on Mollie's page. The list is empty when Mollie has no method for the order, or when the request to Mollie fails. Foster Checkout logs a failed request as a warning. After a failed request, the checkout does not send another request for that gateway for 1 minute, unless Mollie rejected the order's amount or country.

Foster Checkout caches the list for 5 minutes, so a method turned on in the Mollie dashboard can take that long to appear. On the single-page checkout, the list updates when the amount due or billing country changes. The selected method stays selected while it is listed. Otherwise the checkout selects the first method, if any, and shows “Your payment method isn’t available for this order. Choose another.” If a save that runs when the customer clicks pay replaces or removes the selected method, the checkout does not submit the payment.

Commerce sends the webhook URL with each Mollie payment, so the Mollie account needs no webhook setting.

## Small Pics

With **Image transformer** set to Small Pics at **Checkout -> Line Items**, Small Pics transforms line item images in the cart and at checkout. Foster Checkout adds the params in **Image transform params** to each image. Use them for a watermark or any other [Small Pics option](https://github.com/smallpics/craft-smallpics).

Foster Checkout sets each image's width, height and mode. A Small Pics `fit` param replaces **Image fit**. For params set in both the control panel and the config file, see [what overrides what](./settings.md#what-overrides-what).

With **Image transformer** set to Craft, or to Auto (the default) while Small Pics is enabled, line item images use Craft's image transforms. Small Pics serves Craft's transforms when its `transformNativeImages` setting is on, with its `nativeTransformsParams` and not **Image transform params**.

## Imager X

Imager X transforms line item images when **Image transformer** is set to Imager X, or to Auto while Imager X is enabled and Small Pics is not. Foster Checkout passes **Image transform params** as Imager X's transform defaults. Set a nested param, such as `transformerParams`, in `lineItems.imageTransformConfig` in `config/foster-checkout.php`, because the control panel table takes only text values.

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

For the renamed `lineItems.imagerXConfig` key, see [upgrading](../upgrade.md#imager-x-transform-config-renamed).

## Advanced Discounts

The checkout names an applied Advanced Discounts coupon next to its code, as it does a Commerce coupon. It also renders messages from Advanced Discounts in the cart and at checkout, including why a coupon did not apply. On the single-page checkout, the messages update when the customer applies or removes a coupon.
