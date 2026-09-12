# Products that never ship

Services, training seats, downloads: anything a customer buys that nobody boxes up. Commerce treats every variant as shippable unless a plugin says otherwise, so without this rule a service-only cart still asks for a shipping address and a shipping method.

Set the rule at **Checkout -> Products -> Products that never ship**. It is a product condition, the same builder Commerce uses elsewhere, so it can match on product type, a product field, a variant SKU or a price. Leave it empty to ship everything.

## What changes for a matching product

Variants of a matching product count as not shippable for Commerce and for every plugin that asks Commerce, so a rate plugin such as Postie leaves them out of the parcel. Nothing about the product itself changes; the rule is read at checkout time.

A cart holding only matching products skips shipping entirely:

- The single-page checkout shows contact and payment, without the shipping address panel, the shipping method panel, the "same as shipping address" billing choice or the shipping row in the summary.
- The multi-page checkout redirects past the address and shipping steps.

A cart mixing matching and shippable products ships as usual; the matching lines take no space or weight.

## When the store still wants an address

Commerce's **Require shipping address at checkout** store setting wins. With it on, the address panel and step stay for a service-only cart, and payment still needs an address. The shipping method is still skipped, since nothing ships.

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
