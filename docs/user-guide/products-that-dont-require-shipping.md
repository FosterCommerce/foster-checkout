# Products that don’t require shipping

How to mark products such as services, training seats and downloads as not shippable, and what the checkout skips for them. Commerce treats every variant as shippable unless a plugin says otherwise, so without this rule a service-only cart still asks for a shipping address and a shipping method.

Set the rule with **Products that don’t require shipping** at **Checkout -> Addresses**. It is a Commerce product condition, so it can match on product type, a product field, a variant SKU or a price. To ship every product, leave the condition empty.

The condition matches a product, not a variant. A rule on a variant's SKU or price matches the whole product when any variant matches, and every variant of that product counts as not shippable.

## What changes for a matching product

Commerce, and every plugin that checks with Commerce, treat variants of a matching product as not shippable, so a rate plugin such as Postie leaves them out of the parcel. The rule does not change the product; Commerce evaluates the rule whenever Commerce checks whether a variant ships.

A cart with only matching products skips shipping:

- The single-page checkout shows only the **Contact information** and **Payment** panels. It leaves out the **Shipping address** and **Shipping method** panels, the **Same as shipping address** billing choice and the shipping row in the summary.
- The stepped checkout skips the address and shipping steps.

Without saved addresses to pick from, the billing address form is open from the start.

A cart mixing matching and shippable products ships as usual; the matching lines add no weight or dimensions.

## When Commerce requires a shipping address

Commerce's **Require Shipping Address At Checkout** setting at **Commerce -> Settings -> Stores** takes precedence. With it on, the address panel and step stay for a service-only cart, and payment still needs an address. The checkout still skips the shipping method, because no item ships.

## Pin settings in config

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

A key in `config/foster-checkout.php` pins the rule and shows it read-only in the control panel, like any other setting. For the other config keys, see [settings](../reference/settings.md).
