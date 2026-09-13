<?php

namespace fostercommerce\fostercheckout\tests\integration;

use fostercommerce\fostercheckout\events\DefineCheckoutContactEvent;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

final class ContactTest extends CartTestCase
{
	public function testTheOrderEmailIsShownWhenNothingOverridesIt(): void
	{
		$this->requireNoCheckoutHandler(Checkout::EVENT_DEFINE_CONTACT);
		[, $cart] = $this->signedInCustomerCart();

		self::assertSame((string) $cart->email, $this->checkout()->contact($cart));
	}

	public function testAHandlerNamesTheContactInstead(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_CONTACT, static function (DefineCheckoutContactEvent $event): void {
			$event->value = 'Purchasing, Acme Co';
		});

		self::assertSame('Purchasing, Acme Co', $this->checkout()->contact($cart));
	}

	public function testAHandlerLeavingTheValueAloneKeepsTheOrderEmail(): void
	{
		$this->requireNoCheckoutHandler(Checkout::EVENT_DEFINE_CONTACT);
		[, $cart] = $this->signedInCustomerCart();

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_CONTACT, static function (DefineCheckoutContactEvent $event): void {
			$event->value = null;
		});

		self::assertSame((string) $cart->email, $this->checkout()->contact($cart));
	}

	public function testTheHandlerIsGivenTheOrderItNames(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$seen = null;

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_CONTACT, static function (DefineCheckoutContactEvent $event) use (&$seen): void {
			$seen = $event->order->id;
		});
		$this->checkout()->contact($cart);

		self::assertSame($cart->id, $seen);
	}
}
