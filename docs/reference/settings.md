# Settings

You edit most settings under **Checkout**. Set the config-only settings, listed after the defaults, in `config/foster-checkout.php`. That file also overrides the control panel; see [what overrides what](#what-overrides-what).

## Screens

| Screen | Config key | Covers |
| --- | --- | --- |
| **Appearance** | `branding`, `options.enableSinglePageCheckout` and `options.enablePageTransitions` | Whether the checkout runs as one page or separate steps, whether steps animate, and **Brand color**, **Header background color**, **Header text color**, **Font**, **Logo**, **Logo height**, **Component style**, **Field labels** and **Title prefix** |
| **Features** | `options.enableNewsletter`, `options.newsletterListId`, `enableCustomerPickup`, `customerPickupLabel`, `enableSavedPaymentSources`, `savedPaymentSourceDisplay` and `deleteClearedCarts` | Whether the checkout shows the newsletter checkbox and the list it subscribes customers to, whether the shipping address choices include the store location, whether a signed-in customer paying with Stripe can choose a saved payment method and how they are listed, and whether **Clear the cart** moves the cart to the trash. See [customer pickup](../user-guide/customer-pickup.md), [saved payment methods](../user-guide/saved-payment-methods.md), [cart](../user-guide/cart.md) and [integrations](./integrations.md) |
| **Line Items** | `lineItems`, `products` and `lineItemOptionRules` | Which field each product type's preview image comes from, how a line item image fills its box, what transforms it and with which params, and whether a placeholder stands in for a missing one. With `paths.useCartTemplate` on, also **Image size**, **Show the SKU**, **Show the stock count**, **Show line item options**, **Hidden option prefix**, **Truncate values to**, and the rules that rewrite an option's name and value |
| **Gateways** | `paymentGateways` and `zeroValueGatewayHandles` | Per gateway: **Name shown to customers**, and on a Manual gateway, a field layout. For a Stripe gateway, also how its payment element lists payment methods, their order and whether it includes Link. For a PayPal gateway, also which funding sources its buttons show, which card brands its card fields accept, the locale and the SDK components. Also which gateways an order totaling zero can be paid with, except Stripe, PayPal, Authorize.net and Mollie gateways |
| **Custom Fields** | `customerOrderNotesFieldHandle` and the checkout field layouts | The extra fields shown at each checkout position, and the field on Orders that stores a customer's note. Blank hides the order notes form |
| **Addresses** | the keys in the next table, `notShippableProducts`, `addressLookup` and `options.enableAddressVerification` | Which products need no address at all, what the checkout asks for on an address, whether AvaTax verifies it, and where address suggestions come from. A cart with only those products skips the shipping address and method, and Commerce and shipping plugins treat those products as not shippable. See [address fields](../user-guide/address-fields.md) |
| **General** | `paths` and the keys below | Cart, checkout, account and cancel paths, plus the built-in cart template switch |

Keys on the Addresses screen:

| Setting | Config key | Controls |
| --- | --- | --- |
| Default country | `defaultCountryCode` | Country a new address starts on. Blank starts with no country chosen |
| Priority countries | `priorityCountries` | Country codes shown at the top of country dropdowns, in the order listed |
| Saved address limit | `savedAddressLimit` | How many of a customer's saved addresses the checkout lists, most recently updated first. Their primary address is always among them, and so are the addresses the cart already uses. Zero lists every address they have saved |
| Saved address display | `savedAddressDisplay` | How the checkout shows saved addresses: `list`, `dropdown`, or `auto`, which shows a list up to `savedAddressDropdownThreshold` addresses and a dropdown past it. A value other than these three shows the list |
| Dropdown threshold | `savedAddressDropdownThreshold` | The most saved addresses `auto` still shows as a list, counting the addresses the checkout lists. At least 1 |
| Phone field | `addressPhoneFieldHandle` | Handle of the address field that stores a phone number, so its input opens a phone keypad |
| Hidden address fields | `hiddenAddressFields` | Address fields left off the checkout. They stay in the control panel. A field the address layout marks required is always shown |
| Hidden billing address fields | `hiddenBillingAddressFields` | Address fields also left off a new billing address, in addition to the hidden list. A field the address layout marks required is always shown |
| Required shipping address fields | `requiredAddressFields` | Address fields required on a shipping address, and on a saved address a customer edits, beyond the fields the address layout requires. A hidden field is never required |
| Required billing address fields | `requiredBillingAddressFields` | The same for a new billing address |
| Show a third address line | `showAddressLine3` | Whether checkout address forms include a third address line. Off by default |
| Let customers name a saved address | `showAddressLabelField` | Whether the label of a saved address is editable at the checkout. It shows only when a customer edits an address they already saved |
| Show the address label | `showAddressLabelInPreview` | Whether the checkout shows a saved address by the name the customer gave it, in the address choices and the completed steps. An order's address uses the name of the address it was copied from, and the store location is left out |

Other keys on the General screen:

| Setting | Config key | Controls |
| --- | --- | --- |
| Head include, Body include, Summary include | `includes` | Template paths injected into the cart and checkout pages. See [custom includes](../dev-guide/custom-includes.md) |
| Content translation method | `contentTranslationMethod` | See [content translation method](#content-translation-method) |

## Defaults

Every setting and its default, as the plugin ships.

| Setting | Config key | Default |
| --- | --- | --- |
| Brand color | `branding.color` | `#1F2937` |
| Header background color | `branding.headerBgColor` | `#F3F3F3` |
| Header text color | `branding.headerTextColor` | `#1F2937` |
| Font | `branding.font` | `Rubik` |
| Logo | `branding.logo` | empty. A path relative to the web root |
| Logo height | `branding.logoHeight` | `40`. Accepts 12 to 200 |
| Component style | `branding.style` | `rounded`. One of `rounded` or `flat` |
| Field labels | `branding.labelStyle` | `floating`. One of `floating` (**Inside the field**) or `above` (**Above the field**) |
| Title prefix | `branding.title` | empty |
| Favicon set | `branding.faviconConfig` | empty |
| Checkout layout | `options.enableSinglePageCheckout` | `false` |
| Page transitions | `options.enablePageTransitions` | `false` |
| Verify shipping addresses | `options.enableAddressVerification` | `false` |
| Newsletter checkbox | `options.enableNewsletter` | `false` |
| Newsletter list ID | `options.newsletterListId` | empty. Takes a list ID or an environment variable name, such as `$NEWSLETTER_LIST_ID` |
| Payment due date field | `options.paymentDueDateFieldHandle` | empty |
| Address suggestions | `addressLookup.enabled` | `false` |
| Address suggestion provider | `addressLookup.provider` | `google` |
| Address suggestion API key | `addressLookup.apiKey` | empty. Takes an environment variable name, such as `$FC_ADDRESS_LOOKUP_KEY` |
| Image size | `lineItems.imageSize` | `150`, and 75 on narrow screens. Accepts 40 to 200 |
| Image fit | `lineItems.imageFit` | `contain` |
| Image transformer | `lineItems.imageTransformer` | `auto`. One of `auto`, `craft`, `imager-x` or `smallpics` |
| Image transform params | `lineItems.imageTransformConfig` | empty. Values can be environment variable names, such as `$SMALLPICS_WATERMARK_ORIGIN` |
| Placeholder images | `lineItems.enablePlaceholderImages` | `false` |
| Show the SKU | `lineItems.showLineItemSku` | `true` |
| Show the stock count | `lineItems.showLineItemStock` | `true` |
| Show line item options | `lineItems.enableLineItemOptions` | `true` |
| Hidden option prefix | `lineItems.hiddenLineItemOptionPrefix` | `_` |
| Truncate values to | `lineItems.lineItemOptionValueMaxLength` | empty |
| Cart path | `paths.cart` | `cart` |
| Use the built-in cart template | `paths.useCartTemplate` | `true` |
| Checkout path | `paths.checkout` | `checkout` |
| Cancel path | `paths.cancel` | `/` |
| Account path | `paths.account` | `/` |
| Head include | `includes.head` | empty |
| Body include | `includes.body` | empty |
| Summary include | `includes.summary` | empty |
| Newsletter checkbox label | `options.subscribe` | empty |
| Content translation method | `contentTranslationMethod` | `site` |
| Customer order notes field | `customerOrderNotesFieldHandle` | empty |
| Default country | `defaultCountryCode` | empty |
| Priority countries | `priorityCountries` | empty |
| Saved address limit | `savedAddressLimit` | `10` |
| Saved address display | `savedAddressDisplay` | `list` |
| Dropdown threshold | `savedAddressDropdownThreshold` | `5` |
| Phone field | `addressPhoneFieldHandle` | empty |
| Hidden address fields | `hiddenAddressFields` | empty |
| Hidden billing address fields | `hiddenBillingAddressFields` | empty |
| Required shipping address fields | `requiredAddressFields` | empty |
| Required billing address fields | `requiredBillingAddressFields` | empty |
| Show a third address line | `showAddressLine3` | `false` |
| Let customers name a saved address | `showAddressLabelField` | `false` |
| Show the address label | `showAddressLabelInPreview` | `false` |
| Enable customer pickup | `enableCustomerPickup` | `false` |
| Pickup label | `customerPickupLabel` | empty |
| Zero value gateways | `zeroValueGatewayHandles` | empty |
| Enable saved payment methods | `enableSavedPaymentSources` | `false` |
| Saved payment method display | `savedPaymentSourceDisplay` | `address` |
| Delete cleared carts | `deleteClearedCarts` | `false` |
| Line item option rules | `lineItemOptionRules` | empty |
| Preview image fields | `products` | empty |
| Product image field | `products.<handle>.productImageHandle` | empty |
| Variant image field | `products.<handle>.variantImageHandle` | empty |
| Products that don’t require shipping | `notShippableProducts` | empty |
| Payment gateways | `paymentGateways` | empty |
| Name shown to customers | `paymentGateways.<handle>.label` | empty, which shows the gateway's own name |
| Gateway note | `paymentGateways.<handle>.note` | empty |
| Payment method layout (Stripe) | `paymentGateways.<handle>.layout` | `tabs`. One of `tabs`, `accordion` or `auto` |
| Payment method order (Stripe) | `paymentGateways.<handle>.paymentMethodOrder` | empty. Methods marked “Powered by Link”, such as Klarna, come last; this setting cannot rank them |
| Include Link (Stripe) | `paymentGateways.<handle>.enableLink` | `true` |
| Hidden funding sources (PayPal) | `paymentGateways.<handle>.disableFunding` | empty. Takes the [funding sources](https://developer.paypal.com/sdk/js/configuration/#disable-funding) PayPal lists |
| Extra funding sources (PayPal) | `paymentGateways.<handle>.enableFunding` | empty. Takes the same values |
| Turned away card brands (PayPal) | `paymentGateways.<handle>.disableCard` | empty. PayPal has deprecated this parameter |
| Locale (PayPal) | `paymentGateways.<handle>.locale` | empty |
| SDK components (PayPal) | `paymentGateways.<handle>.components` | empty |

Two settings have no control panel field. Set them in `config/foster-checkout.php`: `branding.faviconConfig` and `options.paymentDueDateFieldHandle`.

## What overrides what

The plugin merges the config file over the stored settings on every request, so a key set in the file overrides the stored value. That key is pinned: the control panel shows its field disabled, with a warning naming the key, and the plugin discards a posted value for the key.

> This is being overridden by the `branding.color` setting in the `config/foster-checkout.php` file.

The merge applies per key. Setting `branding.color` in the config file pins that one field and leaves the rest of the Appearance screen editable. To make the field editable again, remove the key from the config file.

A list is pinned whole rather than merged. A config file setting `priorityCountries` replaces the stored list; it does not add to it. The same applies to `hiddenAddressFields`, `hiddenBillingAddressFields`, `requiredAddressFields`, `requiredBillingAddressFields` and `zeroValueGatewayHandles`, except that the plugin ignores Stripe, PayPal, Authorize.net and Mollie handles in `zeroValueGatewayHandles`.

If the config file sets `lineItems.imageTransformConfig`, the **Image transform params** table is read-only, but the plugin merges the two per param. A param saved in the table still applies unless the config file sets the same param. To use only the config file's params, delete the table's rows before adding the key.

The plugin stores a gateway's field layout outside plugin settings, so the layout stays editable even where the config file sets `paymentGateways`.

### Notes & Links copy

Copy works the other way around. `options.subscribe` and `paymentGateways.<handle>.note` are edited at **Checkout -> Notes & Links**. For these two, the stored copy takes precedence, and the plugin uses a config value only while the stored copy is blank. A gateway note written as a PHP closure can be set only in the config file. See [notes and links](../user-guide/content.md).

## Content translation method

How checkout copy varies across sites, using Craft's own field translation methods. On a single-site install all three behave identically.

| Value | Option | Behavior |
| --- | --- | --- |
| `none` | **Not translatable** | One copy shared by every site |
| `site` | **Translate for each site** | A copy per site (default) |
| `language` | **Translate for each language** | Sites speaking the same language share a copy |

## Gateway field layouts

Choose a Manual gateway's fields in Craft's field layout designer. The rules for [checkout fields](../user-guide/checkout-fields.md) apply: you can use only fields already on the order, the checkout supports only the [supported field types](../user-guide/checkout-fields.md#supported-field-types), and you set a field's placeholder and limits on the field itself.
