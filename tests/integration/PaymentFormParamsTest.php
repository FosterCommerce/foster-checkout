<?php

namespace fostercommerce\fostercheckout\tests\integration;

use craft\commerce\base\Gateway;
use fostercommerce\fostercheckout\events\DefinePaymentFormParamsEvent;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * @since 1.4.2
 */
final class PaymentFormParamsTest extends CartTestCase
{
	public function testParamsAreHandedBackUnchangedWhenNoHandlerIsRegistered(): void
	{
		$this->requireNoCheckoutHandler(Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS);
		[, $cart] = $this->signedInCustomerCart();
		$params = [
			'disable-funding' => 'credit',
		];

		self::assertSame($params, $this->checkout()->paymentFormParams($cart, $this->firstGateway(), $params));
	}

	public function testAHandlerCanAddAParam(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS, static function (DefinePaymentFormParamsEvent $event): void {
			$event->params['appearance'] = 'night';
		});

		self::assertSame([
			'layout' => 'tabs',
			'appearance' => 'night',
		], $this->checkout()->paymentFormParams($cart, $this->firstGateway(), [
			'layout' => 'tabs',
		]));
	}

	public function testAHandlerCanReplaceTheWholeSet(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		$this->requireNoCheckoutHandler(Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS);

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS, static function (DefinePaymentFormParamsEvent $event): void {
			$event->params = [];
		});

		self::assertSame([], $this->checkout()->paymentFormParams($cart, $this->firstGateway(), [
			'layout' => 'tabs',
		]));
	}

	public function testTheHandlerIsToldWhichGatewayIsBeingRendered(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$gateway = $this->firstGateway();
		$seen = null;

		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PAYMENT_FORM_PARAMS, static function (DefinePaymentFormParamsEvent $event) use (&$seen): void {
			$seen = $event->gateway->handle;
		});
		$this->checkout()->paymentFormParams($cart, $gateway, []);

		self::assertSame($gateway->handle, $seen);
	}

	private function firstGateway(): Gateway
	{
		$gateway = $this->commerce()->getGateways()->getAllGateways()->first();

		if (! $gateway instanceof Gateway) {
			self::markTestSkipped('This site has no payment gateways.');
		}

		return $gateway;
	}
}
