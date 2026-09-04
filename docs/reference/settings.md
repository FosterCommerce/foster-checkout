# Settings

Every setting is editable in the control panel under **Checkout**. A site may also ship a `config/foster-checkout.php` file, which overrides the control panel.

## Screens

| Screen | Config key | Holds |
| --- | --- | --- |
| **Appearance** | `branding` | Brand color, header background, Google font family, logo path, component style, field label placement, title prefix |
| **Features** | `options` and `addressLookup` | Checkout page layout format, the other `enable*` switches, and the Klaviyo list ID. Blank list ID hides the newsletter checkbox. Multi-page is the default |
| **Line Items** | `lineItems` and `lineItemOptionRules` | Whether a line item shows its SKU, whether its options are shown, which option names are hidden, how far option values are cut, and the rules that rewrite an option's name and value |
| **Products** | `products` | Per product type, the field holding the cart preview image. Blank falls back to the product's own image |
| **Gateways** | `paymentGateways` | Per gateway: a field layout and extra payment form parameters |
| **General** | `paths` and the keys below | Cart, checkout, account and cancel paths, plus the built-in cart template switch |

Other keys on the General screen:

| Setting | Config key | Holds |
| --- | --- | --- |
| Head include, Body include | `includes` | Template paths injected into every cart and checkout page. See [custom includes](../dev-guide/custom-includes.md) |
| Priority countries | `priorityCountries` | Country codes shown at the top of country dropdowns, in the order listed |
| Hidden address fields | `hiddenAddressFields` | Address fields left off the checkout. They stay in the control panel. A field the address layout marks required is always shown |
| Required address fields | `requiredAddressFields` | Address fields required at the checkout beyond what the address layout asks for. A hidden field is never required |
| Zero value gateways | `zeroValueGatewayHandles` | Gateways available when an order totals zero |
| Customer order notes field | `customerOrderNotesFieldHandle` | Field on Orders holding the customer's note. Blank hides the order notes form |
| Content translation method | `contentTranslationMethod` | See below |

## Defaults

Every setting and its default, as the plugin ships.

| Setting | Config key | Default |
| --- | --- | --- |
| Brand color | `branding.color` | `#1F2937` |
| Header background | `branding.headerBgColor` | `#F3F3F3` |
| Google font family | `branding.font` | `Rubik` |
| Logo path | `branding.logo` | empty |
| Component style | `branding.style` | `rounded` |
| Field label placement | `branding.labelStyle` | `floating` |
| Title prefix | `branding.title` | empty |
| Favicon set | `branding.faviconConfig` | empty |
| Checkout layout | `options.enableSinglePageCheckout` | `false` |
| Save for later | `options.enableSaveForLater` | `false` |
| Placeholder images | `options.enablePlaceholderImages` | `false` |
| Page transitions | `options.enablePageTransitions` | `false` |
| Verify shipping addresses | `options.enableAddressVerification` | `false` |
| Shipping estimator | `options.enableEstimatedShipping` | `false` |
| Klaviyo list ID | `options.klaviyoListId` | none |
| Payment due date field | `options.paymentDueDateFieldHandle` | none |
| Imager X transform | `options.imagerXConfig` | none |
| Address suggestions | `addressLookup.enabled` | `false` |
| Suggestion provider | `addressLookup.provider` | `google` |
| Suggestion API key | `addressLookup.apiKey` | none |
| Show line item SKU | `lineItems.showLineItemSku` | `true` |
| Show line item options | `lineItems.enableLineItemOptions` | `true` |
| Hidden option prefix | `lineItems.hiddenLineItemOptionPrefix` | `_` |
| Option value length limit | `lineItems.lineItemOptionValueMaxLength` | none |
| Cart path | `paths.cart` | `cart` |
| Use the built-in cart template | `paths.useCartTemplate` | `true` |
| Checkout path | `paths.checkout` | `checkout` |
| Cancel path | `paths.cancel` | `/` |
| Account path | `paths.account` | `/` |
| Content translation method | `contentTranslationMethod` | `site` |
| Customer order notes field | `customerOrderNotesFieldHandle` | none |
| Priority countries | `priorityCountries` | empty |
| Hidden address fields | `hiddenAddressFields` | empty |
| Required address fields | `requiredAddressFields` | empty |
| Zero value gateways | `zeroValueGatewayHandles` | empty |
| Line item option rules | `lineItemOptionRules` | empty |
| Product image fields | `products` | empty |
| Payment gateway params | `paymentGateways` | empty |

Some settings have no control panel field and are set in `config/foster-checkout.php` only: `branding.faviconConfig`, `options.paymentDueDateFieldHandle`, `options.imagerXConfig`, `options.enableEstimatedShipping` and the delivery date keys.

## What overrides what

The config file is merged over stored settings on every request, so a key set in the file always wins. That field is shown disabled with a warning naming it, and a posted value for a pinned key is discarded server side.

> This is being overridden by the `branding.color` setting in the `config/foster-checkout.php` file.

The merge applies per key. Setting `branding.color` in the config file pins that one field and leaves the rest of the Appearance screen editable. Remove the key from the config file to hand the field back.

A list is pinned whole rather than merged. A config file setting `priorityCountries` replaces the stored list; it does not add to it. The same holds for `hiddenAddressFields`, `requiredAddressFields` and `zeroValueGatewayHandles`.

A gateway's field layout is stored outside plugin settings, so it stays editable even where `paymentGateways` is set in the config file.

## Address suggestions

Suggestions come from Google Places or Loqate, chosen on the Features screen. Neither is included; the site supplies its own account and key.

Set the key to an environment variable name so it stays out of project config:

```sh
FC_ADDRESS_LOOKUP_KEY=your-key-here
```

Then set the control panel's API key field to `$FC_ADDRESS_LOOKUP_KEY`, not the key itself.

Suggestions appear on the shipping address only, and are off until a provider and key are set.

Only one address tool runs at a time. Avalara address verification wins: while it is running, suggestions are skipped, because verification checks the address the customer settled on rather than the one they picked from a list. Turning verification off hands the shipping address to suggestions.

Avalara covers the United States and Canada. It answers `Country not supported` for anywhere else, which the checkout treats as no suggestion, so a store shipping elsewhere gets nothing from verification and nothing to say why. Both suggestion providers work internationally, Loqate the more widely. A store outside the United States and Canada gets more from turning verification off.

**Test the connection** on the Features screen runs one lookup and names the error, which is the only place a wrong key shows.

Both providers bill per lookup, and the endpoint that calls them is public because the checkout is. **Cap the spend at the provider.** In Google Cloud, set a daily and a per-minute quota on the Places API. Loqate sells prepaid credit, which caps itself. The plugin sets no rate limit of its own.

A failed lookup is never shown to the customer. A revoked key, an exhausted account or an unreachable provider returns no suggestions, the reason is written to the log, and the customer types the address as they would with the feature turned off.

## Content translation method

How checkout copy varies across sites, using Craft's own field translation methods. On a single-site install all three behave identically.

| Value | Behavior |
| --- | --- |
| `none` | One copy shared by every site |
| `site` | A copy per site (default) |
| `language` | Sites speaking the same language share a copy |

## Gateway field layouts

A gateway's fields are chosen with Craft's field layout designer, which sets their order, width, label and whether each is required.

**Only fields already on the order can be used.** An order saves values through its own field layout, so a field missing from it would render, accept what the customer types, then be discarded with no error. Saving such a layout is rejected and names the offending handles. Add the field under **Commerce -> Settings -> Order Fields** first.

A field's placeholder and its length or value limits are set on the field itself, not the layout, so they apply everywhere that field is used.

## Settings that stay in config

| Setting | Why |
| --- | --- |
| A gateway `note` | May be a PHP closure, which the control panel cannot represent. The plugin falls back to it whenever the stored copy for that gateway is empty |
| `deliveryDate.estimate`, `deliveryDate.display` | Accept a closure or a Twig string, because they compute a date and a visibility flag rather than holding copy |
