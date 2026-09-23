# Settings

Most settings are edited under **Checkout**. The config-only ones, listed after the defaults, are set in `config/foster-checkout.php`. That file also overrides the control panel; see [what overrides what](#what-overrides-what).

## Screens

| Screen | Config key | Holds |
| --- | --- | --- |
| **Appearance** | `branding`, `options.enableSinglePageCheckout` and `options.enablePageTransitions` | Whether the checkout runs as one page or separate steps, whether steps animate, and **Brand color**, **Header background color**, **Header text color**, **Font**, **Logo**, **Logo height**, **Component style**, **Field labels** and **Title prefix** |
| **Features** | `options.enableKlaviyoTracking`, `options.klaviyoListId`, `enableCustomerPickup` and `customerPickupLabel` | Klaviyo tracking and the Klaviyo list ID, whether the shipping address choices include the store location, and the delivery date copy, shown read-only. The newsletter checkbox needs tracking on, a list ID and the Klaviyo Connect Plus plugin. See [customer pickup](../user-guide/customer-pickup.md) and [integrations](./integrations.md) |
| **Line Items** | `lineItems`, `products` and `lineItemOptionRules` | Which field each product type's preview image comes from, how a line item image fills its box and whether a placeholder stands in for a missing one. With `paths.useCartTemplate` on, also **Image size**, **Show the SKU**, **Show the stock count**, **Show line item options**, **Hidden option prefix**, **Truncate values to**, and the rules that rewrite an option's name and value |
| **Gateways** | `paymentGateways` and `zeroValueGatewayHandles` | Per gateway: **Name shown to customers**, and on a Manual gateway, a field layout. A Stripe gateway also sets how its payment element lists the payment methods, their order, and whether Link is included. A PayPal gateway also sets which funding sources its buttons offer, which card brands its card fields take, plus the locale and SDK components. Also which gateways an order totaling zero can be paid with |
| **Custom Fields** | `customerOrderNotesFieldHandle` and the checkout field layouts | The extra fields shown at each checkout position, and the field on Orders holding a customer's note. Blank hides the order notes form |
| **Addresses** | the keys in the next table, `notShippableProducts`, `addressLookup` and `options.enableAddressVerification` | Which products need no address at all, what the checkout asks for on an address, whether Avalara verifies it, and where address suggestions come from. A cart holding only those products skips the shipping address and method, and Commerce and shipping plugins treat those products as not shippable. See [address fields](../user-guide/address-fields.md) |
| **General** | `paths` and the keys below | Cart, checkout, account and cancel paths, plus the built-in cart template switch |

Keys on the Addresses screen:

| Setting | Config key | Holds |
| --- | --- | --- |
| Default country | `defaultCountryCode` | Country a new address starts on. Blank starts with no country chosen |
| Priority countries | `priorityCountries` | Country codes shown at the top of country dropdowns, in the order listed |
| Saved address limit | `savedAddressLimit` | How many of a customer's saved addresses the checkout offers, most recently updated first. Their primary address is always among them. Zero offers every address they have saved |
| Phone field | `addressPhoneFieldHandle` | Handle of the address field holding a phone number, so its input asks for a phone keypad |
| Hidden address fields | `hiddenAddressFields` | Address fields left off the checkout. They stay in the control panel. A field the address layout marks required is always shown |
| Hidden billing address fields | `hiddenBillingAddressFields` | Address fields also left off a new billing address, on top of the hidden list. A field the address layout marks required is always shown |
| Required shipping address fields | `requiredAddressFields` | Address fields required on a shipping address, and on a saved address a customer edits, beyond what the address layout asks for. A hidden field is never required |
| Required billing address fields | `requiredBillingAddressFields` | The same for a new billing address |
| Show a third address line | `showAddressLine3` | Whether checkout address forms offer a third address line. Off by default |
| Let customers name a saved address | `showAddressLabelField` | Whether the label of a saved address is editable at the checkout. It shows only when a customer edits an address they already saved |
| Show the address label | `showAddressLabelInPreview` | Whether a saved address the customer named is shown by that name in the address choices and the completed steps. An order's address borrows the name of the address it was copied from, and the store location is left out |

Other keys on the General screen:

| Setting | Config key | Holds |
| --- | --- | --- |
| Head include, Body include, Summary include | `includes` | Template paths injected into the cart and checkout pages. See [custom includes](../dev-guide/custom-includes.md) |
| Content translation method | `contentTranslationMethod` | See below |

## Defaults

Every setting and its default, as the plugin ships.

| Setting | Config key | Default |
| --- | --- | --- |
| Brand color | `branding.color` | `#1F2937` |
| Header background color | `branding.headerBgColor` | `#F3F3F3` |
| Header text color | `branding.headerTextColor` | `#1F2937` |
| Font | `branding.font` | `Rubik` |
| Logo | `branding.logo` | empty. A path relative to the web root |
| Logo height | `branding.logoHeight` | `40`. Accepts 40 to 200 |
| Component style | `branding.style` | `rounded`. One of `rounded` or `flat` |
| Field labels | `branding.labelStyle` | `floating`. One of `floating` (**Inside the field**) or `above` (**Above the field**) |
| Title prefix | `branding.title` | empty |
| Favicon set | `branding.faviconConfig` | empty |
| Checkout layout | `options.enableSinglePageCheckout` | `false` |
| Page transitions | `options.enablePageTransitions` | `false` |
| Verify shipping addresses | `options.enableAddressVerification` | `false` |
| Klaviyo tracking | `options.enableKlaviyoTracking` | `false` |
| Shipping estimator | `options.enableEstimatedShipping` | `false` |
| Klaviyo list ID | `options.klaviyoListId` | none. Takes a list ID or an environment variable name, such as `$KLAVIYO_LIST_ID` |
| Payment due date field | `options.paymentDueDateFieldHandle` | none |
| Address suggestions | `addressLookup.enabled` | `false` |
| Address suggestion provider | `addressLookup.provider` | `google` |
| Address suggestion API key | `addressLookup.apiKey` | none. Takes an environment variable name, such as `$FC_ADDRESS_LOOKUP_KEY` |
| Image size | `lineItems.imageSize` | `150`, so narrow screens use 75. Accepts 40 to 200 |
| Image fit | `lineItems.imageFit` | `contain` |
| Placeholder images | `lineItems.enablePlaceholderImages` | `false` |
| Imager X transform | `lineItems.imagerXConfig` | none |
| Show the SKU | `lineItems.showLineItemSku` | `true` |
| Show the stock count | `lineItems.showLineItemStock` | `true` |
| Show line item options | `lineItems.enableLineItemOptions` | `true` |
| Hidden option prefix | `lineItems.hiddenLineItemOptionPrefix` | `_` |
| Truncate values to | `lineItems.lineItemOptionValueMaxLength` | none |
| Cart path | `paths.cart` | `cart` |
| Use the built-in cart template | `paths.useCartTemplate` | `true` |
| Checkout path | `paths.checkout` | `checkout` |
| Cancel path | `paths.cancel` | `/` |
| Account path | `paths.account` | `/` |
| Head include | `includes.head` | empty |
| Body include | `includes.body` | empty |
| Summary include | `includes.summary` | empty |
| Newsletter checkbox label | `options.subscribe` | none |
| Delivery date label, message, estimate, display | `options.deliveryDate.label`, `.message`, `.estimate`, `.display` | none |
| Content translation method | `contentTranslationMethod` | `site` |
| Customer order notes field | `customerOrderNotesFieldHandle` | none |
| Default country | `defaultCountryCode` | empty |
| Priority countries | `priorityCountries` | empty |
| Saved address limit | `savedAddressLimit` | `10` |
| Phone field | `addressPhoneFieldHandle` | none |
| Hidden address fields | `hiddenAddressFields` | empty |
| Hidden billing address fields | `hiddenBillingAddressFields` | empty |
| Required shipping address fields | `requiredAddressFields` | empty |
| Required billing address fields | `requiredBillingAddressFields` | empty |
| Show a third address line | `showAddressLine3` | `false` |
| Let customers name a saved address | `showAddressLabelField` | `false` |
| Show the address label | `showAddressLabelInPreview` | `false` |
| Offer pickup at the store location | `enableCustomerPickup` | `false` |
| Pickup label | `customerPickupLabel` | none |
| Zero value gateways | `zeroValueGatewayHandles` | empty |
| Line item option rules | `lineItemOptionRules` | empty |
| Preview image fields | `products` | empty |
| Product image field | `products.<handle>.productImageHandle` | none |
| Variant image field | `products.<handle>.variantImageHandle` | none |
| Products that don’t require shipping | `notShippableProducts` | empty |
| Payment gateways | `paymentGateways` | empty |
| Name shown to customers | `paymentGateways.<handle>.label` | empty, so the gateway's own name is used |
| Gateway note | `paymentGateways.<handle>.note` | empty |
| Payment method layout (Stripe) | `paymentGateways.<handle>.layout` | `tabs`. One of `tabs`, `accordion` or `auto` |
| Payment method order (Stripe) | `paymentGateways.<handle>.paymentMethodOrder` | empty. A method marked “Powered by Link”, such as Klarna, cannot be ranked and is listed last |
| Include Link (Stripe) | `paymentGateways.<handle>.enableLink` | `true` |
| Hidden funding sources (PayPal) | `paymentGateways.<handle>.disableFunding` | empty. Takes the [funding sources](https://developer.paypal.com/sdk/js/configuration/#disable-funding) PayPal lists |
| Extra funding sources (PayPal) | `paymentGateways.<handle>.enableFunding` | empty. Takes the same values |
| Turned away card brands (PayPal) | `paymentGateways.<handle>.disableCard` | empty. PayPal has deprecated this one |
| Locale (PayPal) | `paymentGateways.<handle>.locale` | empty |
| SDK components (PayPal) | `paymentGateways.<handle>.components` | empty |

Some settings have no control panel field and are set in `config/foster-checkout.php` only: `branding.faviconConfig`, `options.paymentDueDateFieldHandle`, `lineItems.imagerXConfig`, `options.enableEstimatedShipping`, `options.deliveryDate.estimate` and `options.deliveryDate.display`. The last two accept a closure or a Twig string, because they compute a date and a visibility flag rather than holding copy.

The delivery date label and message are shown read-only under **Checkout -> Features**, because no plugin template renders a delivery date. A site template calls `craft.fostercheckout.getDeliveryDate(order)` to show one.

`options.enableEstimatedShipping` is unfinished.

## What overrides what

The config file is merged over stored settings on every request, so a key set in the file wins. That field is shown disabled with a warning naming it, and a posted value for a pinned key is discarded server side.

> This is being overridden by the `branding.color` setting in the `config/foster-checkout.php` file.

The merge applies per key. Setting `branding.color` in the config file pins that one field and leaves the rest of the Appearance screen editable. Remove the key from the config file to make the field editable again.

A list is pinned whole rather than merged. A config file setting `priorityCountries` replaces the stored list; it does not add to it. The same holds for `hiddenAddressFields`, `hiddenBillingAddressFields`, `requiredAddressFields`, `requiredBillingAddressFields` and `zeroValueGatewayHandles`.

A gateway's field layout is stored outside plugin settings, so it stays editable even where `paymentGateways` is set in the config file.

### Notes & Links copy

Copy works the other way round. `options.subscribe` and `paymentGateways.<handle>.note` are edited at **Checkout -> Notes & Links**. For these two and the delivery date label and message, the stored copy wins, and the plugin uses a config value only while the stored copy is blank. A gateway note written as a PHP closure is set in the config file only. See [notes and links](../user-guide/content.md).

A site upgraded from a `deliveryDate` config key has its label and message stored already, so a config change to them has no effect, and no screen clears them.

## Address verification and suggestions

To set up Avalara verification or address suggestions, see [address fields](../user-guide/address-fields.md#address-verification-and-suggestions).

## Content translation method

How checkout copy varies across sites, using Craft's own field translation methods. On a single-site install all three behave identically.

| Value | Option | Behavior |
| --- | --- | --- |
| `none` | **Not translatable** | One copy shared by every site |
| `site` | **Translate for each site** | A copy per site (default) |
| `language` | **Translate for each language** | Sites speaking the same language share a copy |

## Gateway field layouts

A Manual gateway's fields are chosen with Craft's field layout designer. The same rules apply as for checkout fields: only fields already on the order can be used, only some field types are supported, and a field's placeholder and limits are set on the field itself. See [checkout fields](../user-guide/checkout-fields.md).
