<?php

namespace fostercommerce\fostercheckout\tests\integration;

use fostercommerce\fostercheckout\events\DefinePaymentBlockEvent;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * @since 1.4.1
 */
final class PaymentBlockTest extends CartTestCase
{
	public function testNothingBlocksPaymentWhenNoHandlerIsRegistered(): void
	{
		$this->requireNoCheckoutHandler(Checkout::EVENT_DEFINE_PAYMENT_BLOCK);
		[, $cart] = $this->signedInCustomerCart();

		self::assertNull($this->checkout()->paymentBlock($cart));
	}

	public function testAHandlerGivesTheReasonShownInPlaceOfTheForm(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_BLOCK, static function (DefinePaymentBlockEvent $event): void {
			$event->reason = 'Your account is on credit hold.';
		});

		self::assertSame('Your account is on credit hold.', $this->checkout()->paymentBlock($cart));
	}

	public function testAHandlerThatSetsNoReasonLeavesPaymentOpen(): void
	{
		$this->requireNoCheckoutHandler(Checkout::EVENT_DEFINE_PAYMENT_BLOCK);
		[, $cart] = $this->signedInCustomerCart();

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_BLOCK, static function (DefinePaymentBlockEvent $event): void {
			$event->reason = null;
		});

		self::assertNull($this->checkout()->paymentBlock($cart));
	}

	/**
	 * Re-read on every single-page save, so a reason the customer can fix clears without a reload.
	 */
	public function testTheReasonIsCarriedInTheLiveState(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_BLOCK, static function (DefinePaymentBlockEvent $event): void {
			$event->reason = 'Add a purchase order number.';
		});

		self::assertSame('Add a purchase order number.', $this->checkout()->checkoutLiveState($cart)['paymentBlock']);
	}

	public function testTheHandlerIsGivenTheOrderItBlocks(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$seen = null;

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_BLOCK, static function (DefinePaymentBlockEvent $event) use (&$seen): void {
			$seen = $event->order->id;
		});
		$this->checkout()->paymentBlock($cart);

		self::assertSame($cart->id, $seen);
	}
}
