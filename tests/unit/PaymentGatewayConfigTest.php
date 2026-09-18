<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\PaymentGatewayConfig;
use fostercommerce\fostercheckout\models\ValueConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PaymentGatewayConfigTest extends CheckoutTestCase
{
	public function testABlankLabelFallsBackToTheGatewaysOwnName(): void
	{
		$config = new PaymentGatewayConfig('stripe');

		self::assertSame('stripe', $config->handle);
		self::assertSame('', $config->label);
		self::assertInstanceOf(ValueConfig::class, $config->note);
	}

	public function testPayPalFundingSettingsAreKept(): void
	{
		$config = new PaymentGatewayConfig('paypalCheckout', [
			'disableFunding' => ['credit', 'paylater'],
			'enableFunding' => ['venmo'],
			'disableCard' => ['amex'],
			'locale' => 'en_US',
			'components' => 'buttons,messages',
		]);

		self::assertTrue($config->validate());
		self::assertSame(['credit', 'paylater'], $config->disableFunding);
		self::assertSame(['venmo'], $config->enableFunding);
		self::assertSame(['amex'], $config->disableCard);
		self::assertSame('en_US', $config->locale);
		self::assertSame('buttons,messages', $config->components);
	}

	/**
	 * PayPal's SDK script rejects the whole URL, so the buttons never render.
	 */
	public function testAnUnknownFundingSourceIsInvalid(): void
	{
		$config = new PaymentGatewayConfig('paypalCheckout', [
			'disableFunding' => ['applepay'],
		]);

		self::assertFalse($config->validate());
		self::assertArrayHasKey('disableFunding', $config->getErrors());
	}

	/**
	 * Loose comparison counted `true` as equal to the first source in the range.
	 */
	public function testANonStringFundingSourceIsInvalid(): void
	{
		$config = new PaymentGatewayConfig('paypalCheckout', [
			'disableFunding' => [true],
		]);

		self::assertFalse($config->validate());
		self::assertArrayHasKey('disableFunding', $config->getErrors());
	}

	public function testAnUnknownStripeLayoutIsInvalid(): void
	{
		$config = new PaymentGatewayConfig('stripe', [
			'layout' => 'bogus',
		]);

		self::assertFalse($config->validate());
		self::assertArrayHasKey('layout', $config->getErrors());
	}

	/**
	 * Dropping them keeps a `devMode` site from throwing on the deprecation.
	 *
	 * @param array<array-key, mixed> $config
	 */
	#[DataProvider('removedSettings')]
	public function testARemovedSettingIsDroppedRatherThanSet(string $setting, array $config): void
	{
		$gatewayConfig = new PaymentGatewayConfig('manual', $config + [
			'label' => 'Purchase order',
		]);

		self::assertSame('Purchase order', $gatewayConfig->label);
		self::assertFalse($gatewayConfig->hasProperty($setting));
	}

	/**
	 * @return array<string, array{string, array<array-key, mixed>}>
	 */
	public static function removedSettings(): array
	{
		return [
			'fields' => ['fields', [
				'fields' => ['poNumber'],
			]],
			'params' => ['params', [
				'params' => [
					'disable-funding' => 'credit',
				],
			]],
		];
	}
}
