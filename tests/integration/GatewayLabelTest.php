<?php

namespace fostercommerce\fostercheckout\tests\integration;

use craft\commerce\base\Gateway;
use fostercommerce\fostercheckout\models\PaymentGatewayConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

/**
 * @since 1.1.0
 */
final class GatewayLabelTest extends CheckoutTestCase
{
	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		// Reads Commerce before it reads the plugin, so the skip has to come first
		$this->plugin();
	}

	public function testNoGatewayHasNoLabel(): void
	{
		self::assertSame('', $this->checkout()->gatewayLabel(null));
	}

	/**
	 * A blank label falls back to the gateway's own name.
	 */
	public function testEveryGatewayOnTheSiteIsNamed(): void
	{
		$gateways = $this->commerce()->getGateways()->getAllGateways();

		if ($gateways->isEmpty()) {
			self::markTestSkipped('This site has no payment gateways.');
		}

		/** @var Gateway $gateway */
		foreach ($gateways as $gateway) {
			self::assertNotSame('', $this->checkout()->gatewayLabel($gateway), "{$gateway->handle} has no label");
		}
	}

	public function testAConfiguredLabelReplacesTheGatewaysName(): void
	{
		$gateway = $this->firstGateway();
		$settings = $this->settings();
		$original = $settings->paymentGateways;

		$settings->paymentGateways[(string) $gateway->handle] = new PaymentGatewayConfig((string) $gateway->handle, [
			'label' => 'Pay by card',
		]);

		try {
			self::assertSame('Pay by card', $this->checkout()->gatewayLabel($gateway));
		} finally {
			$settings->paymentGateways = $original;
		}
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
