<?php

namespace fostercommerce\fostercheckout\tests\integration;

use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class OrderSummaryRowsTest extends CheckoutTestCase
{
	public function testSameNamedOrderDiscountsMergeIntoOneRow(): void
	{
		$order = $this->orderWith([
			$this->adjustment('discount', 'Promo', -5),
			$this->adjustment('discount', 'Promo', -7),
		]);

		self::assertSame([[
			'name' => 'Promo',
			'amountAsCurrency' => '-$12.00',
		]], $this->checkout()->orderDiscounts($order));
	}

	public function testDifferentlyNamedDiscountsStaySeparate(): void
	{
		$order = $this->orderWith([
			$this->adjustment('discount', 'Promo', -5),
			$this->adjustment('discount', 'Sale', -7),
		]);

		self::assertSame(['Promo', 'Sale'], array_column($this->checkout()->orderDiscounts($order), 'name'));
	}

	public function testLineLevelDiscountsHaveNoRow(): void
	{
		$order = $this->orderWith([
			$this->adjustment('discount', '10% off', -100, 1),
			$this->adjustment('discount', '10% off', -500, 2),
		]);

		self::assertSame([], $this->checkout()->orderDiscounts($order));
		self::assertSame([], $this->checkout()->afterTaxDiscounts($order));
	}

	public function testADiscountStoredAfterTaxShowsAsAfterTax(): void
	{
		$order = $this->orderWith([
			$this->adjustment('discount', 'Before', -5),
			$this->adjustment('tax', 'Sales Tax', 3),
			$this->adjustment('discount', 'After', -9),
		]);

		self::assertSame(['Before'], array_column($this->checkout()->orderDiscounts($order), 'name'));
		self::assertSame(['After'], array_column($this->checkout()->afterTaxDiscounts($order), 'name'));
	}

	public function testLineLevelTaxAlsoMarksLaterDiscountsAsAfterTax(): void
	{
		$order = $this->orderWith([
			$this->adjustment('tax', 'Sales Tax', 3, 1),
			$this->adjustment('discount', 'After', -9),
		]);

		self::assertSame(['After'], array_column($this->checkout()->afterTaxDiscounts($order), 'name'));
	}

	public function testAnUnknownAdjustmentTypeGetsItsOwnMergedRow(): void
	{
		$order = $this->orderWith([
			$this->adjustment('fee', 'Handling', 4),
			$this->adjustment('fee', 'Handling', 6, 1),
			$this->adjustment('shipping', 'FedEx', 20),
			$this->adjustment('tax', 'Sales Tax', 3),
			$this->adjustment('discount', 'Promo', -5),
			$this->adjustment('voucher', 'Gift card', -10),
		]);

		self::assertSame([[
			'name' => 'Handling',
			'amountAsCurrency' => '$10.00',
		]], $this->checkout()->otherAdjustments($order));
	}

	public function testIncludedAdjustmentsAreLeftOutOfOtherRows(): void
	{
		$order = $this->orderWith([
			$this->adjustment('fee', 'Included fee', 4, null, true),
		]);

		self::assertSame([], $this->checkout()->otherAdjustments($order));
	}

	public function testMergedAmountsAvoidFloatDrift(): void
	{
		$order = $this->orderWith([
			$this->adjustment('discount', 'Promo', -0.1),
			$this->adjustment('discount', 'Promo', -0.2),
		]);

		self::assertSame('-$0.30', $this->checkout()->orderDiscounts($order)[0]['amountAsCurrency']);
	}

	public function testAnOrderWithNoAdjustmentsHasNoRows(): void
	{
		$order = $this->orderWith([]);

		self::assertSame([], $this->checkout()->orderDiscounts($order));
		self::assertSame([], $this->checkout()->afterTaxDiscounts($order));
		self::assertSame([], $this->checkout()->otherAdjustments($order));
	}

	/**
	 * @param list<OrderAdjustment> $adjustments
	 */
	private function orderWith(array $adjustments): Order
	{
		$order = new Order();
		$order->currency = 'USD';
		$order->setAdjustments($adjustments);

		return $order;
	}

	private function adjustment(string $type, string $name, float $amount, ?int $lineItemId = null, bool $included = false): OrderAdjustment
	{
		return new OrderAdjustment([
			'type' => $type,
			'name' => $name,
			'amount' => $amount,
			'lineItemId' => $lineItemId,
			'included' => $included,
		]);
	}
}
