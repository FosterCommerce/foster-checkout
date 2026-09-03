# Line items

What each line in the cart shows, and how to change the wording of its options.

Everything here is under **Checkout -> Line Items**.

## What a line shows

| Setting | Controls |
| --- | --- |
| **Show the SKU** | Whether the SKU appears under the product title. On by default |
| **Show line item options** | Whether options appear at all. Off hides every option and stops the rules below from running. On by default |
| **Hidden option prefix** | Options whose name starts with this are never shown. Default `_`. Leave empty to show every option |
| **Truncate values to** | How many characters of an option value to show. Empty shows the whole value |

Truncation cuts on a word boundary and ends with an ellipsis. It matters where a customer types
free text: a "special instructions" option can run to thousands of characters and fill the cart.

## Options as the customer sees them

An option is a name and a value stored against the line when the item was added. Without any
rules, both show exactly as stored, so an option named `blessing` holding `true` reads:

```
blessing: true
```

Rewrite rules change that wording. They do not change what is stored, only what the customer
reads.

## Rewrite rules

Each rule has a condition and up to two changes: a new name, a new value. A rule with no new
value leaves the customer's own text alone, which is what you want for anything they typed.

To add one, click **Add a rule**, build the condition, and fill in either field. Leave a field
empty to keep what was stored.

The condition offers two things to test, **Option name** and **Option value**, each with the
usual operators: is, begins with, ends with, contains, is empty, is not empty. Add a second
condition to a rule and both must match.

Turning `blessing: true` into `Blessing Services: Yes` takes one rule:

| | |
| --- | --- |
| When | Option name **is** `blessing` |
| Set name to | `Blessing Services` |
| Set value to | `Yes` |

The rules table spells each condition out, so you can read what a rule does without opening it.

### Order

Rules run top to bottom, and the last one to set a field wins. Drag a row to reorder.

Every rule tests the option **as it was stored**, not as an earlier rule rewrote it. So a rule
matching `blessing` still matches after an earlier rule renamed it, and you can split the naming
and the value across two rules:

| When | Set name to | Set value to |
| --- | --- | --- |
| Option name is `blessing` | Blessing Services | |
| Option name is `blessing` and Option value is `true` | | Yes |

An option matching no rule shows as stored. Adding the first rule changes nothing else on the
page.

## Setting these in config

A site may pin any of these in `config/foster-checkout.php`. A pinned setting shows in the
control panel with a warning and cannot be edited there. Pinning one leaves the others editable.
Rules set in a config file are listed but not clickable, since there is nothing to edit.
See [settings](../reference/settings.md).
