<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\elements\Product;
use craft\commerce\elements\Variant;
use craft\commerce\models\LineItem;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * Answer Commerce's shippable event with false for a variant of a matching product.
 *
 * @since 1.3.0
 */
final class ShippabilityTest extends CartTestCase
{
	public function testAnEmptyConditionShipsEverything(): void
	{
		$variant = $this->firstNotShippableVariant();
		$settings = $this->settings();
		$original = $settings->notShippableProducts;
		$settings->notShippableProducts = [];

		try {
			self::assertTrue($this->isShippable($variant));
		} finally {
			$settings->notShippableProducts = $original;
		}
	}

	public function testAMatchingProductsVariantsDoNotShip(): void
	{
		self::assertFalse($this->isShippable($this->firstNotShippableVariant()));
	}

	public function testAProductOutsideTheConditionStillShips(): void
	{
		self::assertTrue($this->isShippable($this->firstShippableVariant()));
	}

	/**
	 * The checkout steps and the single-page panels read this to decide whether to collect delivery.
	 */
	public function testACartOfMatchingItemsHasNothingToShip(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$this->addToCart($cart, $this->firstNotShippableVariant());

		self::assertFalse($cart->hasShippableItems());
	}

	public function testACartWithOneShippableItemStillShips(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$this->addToCart($cart, $this->firstNotShippableVariant());
		$this->addToCart($cart, $this->firstShippableVariant());

		self::assertTrue($cart->hasShippableItems());
	}

	public function testALineItemCarriesTheConditionsAnswer(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$lineItem = $this->addToCart($cart, $this->firstNotShippableVariant());

		self::assertFalse($lineItem->getIsShippable());
	}

	private function isShippable(Variant $variant): bool
	{
		return $this->commerce()->getPurchasables()->isPurchasableShippable($variant);
	}

	private function addToCart(Order $cart, Variant $variant): LineItem
	{
		$lineItem = $this->commerce()->getLineItems()->create($cart, [
			'purchasableId' => $variant->id,
		]);
		$cart->addLineItem($lineItem);

		self::assertTrue(
			Craft::$app->getElements()->saveElement($cart, false),
			'The cart could not be saved: ' . json_encode($cart->getErrors())
		);

		return $lineItem;
	}

	private function firstNotShippableVariant(): Variant
	{
		if ($this->settings()->notShippableProducts === []) {
			self::markTestSkipped('This site has no “products that never ship” condition.');
		}

		$variant = $this->firstVariantMatching(true);

		if (! $variant instanceof Variant) {
			self::markTestSkipped('No enabled variant on this site matches the condition.');
		}

		return $variant;
	}

	private function firstShippableVariant(): Variant
	{
		$variant = $this->firstVariantMatching(false);

		if (! $variant instanceof Variant) {
			self::markTestSkipped('Every product on this site matches the condition.');
		}

		return $variant;
	}

	private function firstVariantMatching(bool $matchesCondition): ?Variant
	{
		$condition = $this->settings()->getNotShippableProductsCondition();
		$hasRule = $this->settings()->notShippableProducts !== [];

		$variants = Variant::find()->status('enabled')->limit(2000)->each();

		/** @var Variant $variant */
		foreach ($variants as $variant) {
			$product = $variant->getProduct();

			if (! $product instanceof Product) {
				continue;
			}

			if (! $variant->getIsAvailable()) {
				continue;
			}

			if (($hasRule && $condition->matchElement($product)) === $matchesCondition) {
				return $variant;
			}
		}

		return null;
	}
}
