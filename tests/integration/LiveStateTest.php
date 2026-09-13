<?php

namespace fostercommerce\fostercheckout\tests\integration;

use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * The state the single-page checkout re-reads after every save.
 */
final class LiveStateTest extends CartTestCase
{
	public function testEveryPanelTheCheckoutRefreshesIsCarried(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertSame([
			'shippingMethods',
			'shippingMethodHandle',
			'totals',
			'lineItemTotals',
			'addressLabels',
			'shippingPreview',
			'customerPickup',
			'couponName',
			'couponMessages',
			'paymentBlock',
		], array_keys($this->checkout()->checkoutLiveState($cart)));
	}

	public function testEachValueHasTheShapeTheTemplatesRead(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$state = $this->checkout()->checkoutLiveState($cart);

		self::assertIsList($state['shippingMethods']);
		self::assertIsString($state['shippingMethodHandle']);
		self::assertIsArray($state['totals']);
		self::assertIsArray($state['lineItemTotals']);
		self::assertIsArray($state['addressLabels']);
		self::assertIsString($state['shippingPreview']);
		self::assertIsBool($state['customerPickup']);
		self::assertIsList($state['couponMessages']);

		if (! $this->checkout()->hasEventHandlers(Checkout::EVENT_DEFINE_PAYMENT_BLOCK)) {
			self::assertNull($state['paymentBlock']);
		}
	}

	/**
	 * The handle names one of the offered methods, so the panel cannot preselect an unrendered one.
	 */
	public function testTheChosenShippingMethodIsOneOfTheOfferedOnes(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$state = $this->checkout()->checkoutLiveState($cart);

		if ($state['shippingMethods'] === []) {
			self::assertSame('', $state['shippingMethodHandle']);

			return;
		}

		self::assertContains($state['shippingMethodHandle'], array_column($state['shippingMethods'], 'handle'));
	}

	public function testAnEmptyCartGetsAnEmptyShippingPreview(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertSame('', $this->checkout()->checkoutLiveState($cart)['shippingPreview']);
	}

	/**
	 * @since 1.4.2
	 */
	public function testAShippingPanelWithNothingToOfferStillHasCopy(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertNotSame('', $this->checkout()->noShippingMethodsText($cart));
	}
}
