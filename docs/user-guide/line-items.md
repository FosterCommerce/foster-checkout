# Line items

The image each line in the cart shows, what else it shows, and how to change the wording of its options.

Everything here is under **Checkout -> Line Items**. **Image size**, the settings under [What a line shows](#what-a-line-shows), and the rewrite rules appear only while **Use the built-in cart template** is on at **Checkout -> General**.

The checkout summary and the order confirmation show each line's image, title, quantity and price, without the SKU, stock count or options.

## Images

| Setting | Controls |
| --- | --- |
| **Preview image fields** | Per product type, the field the cart image comes from. The cart uses the variant field when the variant has an image, and the product field otherwise |
| **Image size** | How wide the image is in the cart, in pixels. Screens narrower than 768 pixels show it at half that width, and screens narrower than 640 pixels hide it. 150 by default. Accepts 40 to 200 |
| **Image fit** | Whether the whole image is shown inside a square box, or cropped to fill it. **Contain** by default |
| **Image transformer** | What transforms the images: Craft, Imager X or Small Pics. Auto by default, which uses Imager X when it is enabled and Small Pics is not, and Craft otherwise. The list includes Imager X and Small Pics while each is enabled or selected. If the selected plugin is disabled or uninstalled, the images use Craft's transforms and the plugin logs a warning |
| **Image transform params** | Parameters sent with each image, such as a watermark, when **Image transformer** uses Imager X or Small Pics. Values can be environment variables, and the plugin sends `true`, `false` and numbers as those types. For each plugin's parameters, see [plugin integrations](../reference/integrations.md) |
| **Placeholder images** | Whether a line item with no image shows a "no image" placeholder, loaded from placehold.co. Off by default |

A product type with neither image field set shows no image, and the line item's text fills the row.

**Contain** leaves the image at its own shape and centers it on white.

## What a line shows

| Setting | Controls |
| --- | --- |
| **Show the SKU** | Whether the SKU appears under the product title. On by default |
| **Show the stock count** | Whether the cart says how many are left in stock. On by default. The quantity stepper still names the count when a customer asks for more than the stock |
| **Show line item options** | Whether options appear at all. Off hides every option and stops the rules below from running. On by default |
| **Hidden option prefix** | The cart never shows an option whose name starts with this prefix. Default `_`. Leave empty to show every option |
| **Truncate values to** | How many characters of an option value to show. Empty shows the whole value |

Truncation cuts on a word boundary and ends with an ellipsis. Truncation matters for free-text options: a "special instructions" option can be thousands of characters long.

## Options as the customer sees them

An option is a name and a value saved on the line item when the item was added. Without any rules, both show exactly as stored, so an option named `giftWrap` holding `1` reads:

```text
giftWrap: 1
```

Rewrite rules change the wording the customer reads. They do not change the stored option.

## Rewrite rules

Each rule has a condition and up to two changes: a new name and a new value. A rule with no new value leaves the stored value unchanged.

To add one, click **Add a rule**, build the condition, and fill in **Set name to**, **Set value to**, or both. Leave a field empty to keep what was stored.

The condition tests **Option name** and **Option value**, each with these operators: is, begins with, ends with, contains, is empty, is not empty. When a rule has two conditions, both must match.

Turning `giftWrap: 1` into `Gift wrap: Yes` takes one rule:

| Field | Setting |
| --- | --- |
| When | Option name **is** `giftWrap` |
| Set name to | `Gift wrap` |
| Set value to | `Yes` |

The rules table shows each rule's condition in full.

### Order

Rules run top to bottom, and when two rules set the same field, the later rule applies.

Every rule tests the option **as it was stored**, not as an earlier rule rewrote it. So a rule matching `giftWrap` still matches after an earlier rule renamed it, and you can split the naming and the value across two rules:

| When | Set name to | Set value to |
| --- | --- | --- |
| Option name is `giftWrap` | `Gift wrap` | |
| Option name is `giftWrap` and Option value is `1` | | `Yes` |

An option matching no rule shows as stored. Adding the first rule does not change how other options show.

## Pin settings in config

You can pin any line item setting in `config/foster-checkout.php`. The control panel shows a pinned setting with a warning and does not let you edit it. Pinning one leaves the others editable. The control panel lists rules set in a config file but does not open them. For the config keys, see [settings](../reference/settings.md).
