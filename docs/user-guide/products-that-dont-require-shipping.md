# Products that don’t require shipping

How to mark products such as services, training seats and downloads as not shippable, and what the checkout skips for them. Commerce treats every variant as shippable unless a plugin says otherwise, so without this rule a service-only cart still asks for a shipping address and a shipping method.

Set the rule at **Checkout -> Addresses -> Products that don’t require shipping**. It is a product condition, the same builder Commerce uses elsewhere, so it can match on product type, a product field, a variant SKU or a price. Leave it empty to ship everything.

The condition matches a product, not a variant. A rule on a variant's SKU or price matches the whole product when any variant matches, and every variant of that product stops shipping.

## What changes for a matching product

Variants of a matching product count as not shippable for Commerce and for every plugin that asks Commerce, so a rate plugin such as Postie leaves them out of the parcel. The rule does not change the product; Commerce evaluates it whenever it checks whether a variant ships.

A cart holding only matching products skips shipping:

- The single-page checkout shows the **Contact information** and **Payment** panels, without the shipping address panel, the shipping method panel, the **Same as shipping address** billing choice or the shipping row in the summary.
- The stepped checkout redirects past the address and shipping steps.

Without saved addresses to pick from, the billing address form is open from the start.

A cart mixing matching and shippable products ships as usual; the matching lines add no weight or dimensions.

## When the store still wants an address

Commerce's **Require Shipping Address At Checkout** setting at **Commerce -> Settings -> Stores** wins. With it on, the address panel and step stay for a service-only cart, and payment still needs an address. The shipping method is still skipped, since no item ships.

## In config

```php
'notShippableProducts' => [
    'class' => \craft\commerce\elements\conditions\products\ProductCondition::class,
    'conditionRules' => [
        [
            'class' => \craft\commerce\elements\conditions\products\ProductTypeConditionRule::class,
            'operator' => 'in',
            'values' => ['<product type uid>'],
        ],
    ],
],
```

A key in `config/foster-checkout.php` pins the rule and shows it read-only in the control panel, like any other setting. See [settings](../reference/settings.md).
