<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\commerce\models\Discount;
use craft\commerce\models\OrderAdjustment;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * @since 1.2.0
 */
final class CouponTest extends CartTestCase
{
	public function testACartWithoutACouponHasNoCouponName(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertNull($this->checkout()->couponName($cart));
	}

	/**
	 * A code whose discount was disabled or deleted used to throw on every cart render.
	 */
	public function testACodeWithNoDiscountBehindItIsNotAnError(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$cart->couponCode = 'FC-TEST-NO-SUCH-CODE';

		self::assertNull($this->checkout()->couponName($cart));
	}

	public function testAnAppliedCommerceCouponIsNamedAfterItsDiscount(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$couponDiscount = null;
		$discounts = $this->commerce()->getDiscounts()->getAllDiscounts();

		foreach ($discounts as $discount) {
			if ($discount instanceof Discount && $discount->getCoupons() !== []) {
				$couponDiscount = $discount;
				break;
			}
		}

		if (! $couponDiscount instanceof Discount) {
			self::markTestSkipped('This site has no discount with a coupon code.');
		}

		$cart->couponCode = $couponDiscount->getCoupons()[0]->code;

		self::assertSame($couponDiscount->name, $this->checkout()->couponName($cart));
	}

	public function testCouponMessagesAreEmptyWithoutAdvancedDiscounts(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$messages = $this->checkout()->couponMessages($cart);

		if (Craft::$app->getPlugins()->isPluginEnabled('advanced-discounts')) {
			self::assertIsList($messages);

			return;
		}

		self::assertSame([], $messages);
	}

	/**
	 * Read the snapshotted code, since the adjustment description is translated and cannot be parsed.
	 */
	public function testAVoucherIsLabelledByItsSnapshottedCode(): void
	{
		$adjustment = new OrderAdjustment();
		$adjustment->sourceSnapshot = [
			'codeKey' => 'GV-4T7K',
		];

		self::assertSame('GV-4T7K', $this->checkout()->voucherCode($adjustment));
		self::assertSame('GV-4T7K', $this->checkout()->voucherLabel($adjustment));
	}

	public function testAVoucherWithoutACodeFallsBackToATranslatedLabel(): void
	{
		$adjustment = new OrderAdjustment();
		$adjustment->sourceSnapshot = [];

		self::assertNull($this->checkout()->voucherCode($adjustment));
		self::assertNotSame('', $this->checkout()->voucherLabel($adjustment));
	}
}
