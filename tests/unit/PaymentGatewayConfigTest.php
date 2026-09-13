<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\PaymentGatewayConfig;
use fostercommerce\fostercheckout\models\ValueConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class PaymentGatewayConfigTest extends CheckoutTestCase
{
	public function testABlankLabelFallsBackToTheGatewaysOwnName(): void
	{
		$config = new PaymentGatewayConfig('stripe');

		self::assertSame('stripe', $config->handle);
		self::assertSame('', $config->label);
		self::assertSame([], $config->params);
		self::assertInstanceOf(ValueConfig::class, $config->note);
	}

	public function testExtraParametersAreKept(): void
	{
		$config = new PaymentGatewayConfig('paypalCheckout', [
			'params' => [
				'disable-funding' => 'credit',
			],
		]);

		self::assertSame([
			'disable-funding' => 'credit',
		], $config->params);
	}

	/**
	 * Dropping it keeps a `devMode` site from throwing on the deprecation.
	 */
	public function testTheRemovedFieldsSettingIsDroppedRatherThanSet(): void
	{
		$config = new PaymentGatewayConfig('manual', [
			'fields' => ['poNumber'],
			'label' => 'Purchase order',
		]);

		self::assertSame('Purchase order', $config->label);
		self::assertFalse($config->hasProperty('fields'));
	}
}
