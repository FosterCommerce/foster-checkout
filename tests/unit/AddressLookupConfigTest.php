<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\AddressLookupConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class AddressLookupConfigTest extends CheckoutTestCase
{
	public function testLookupIsOffByDefault(): void
	{
		$config = new AddressLookupConfig();

		self::assertFalse($config->enabled);
		self::assertSame(AddressLookupConfig::PROVIDER_GOOGLE, $config->provider);
		self::assertNull($config->apiKey);
	}

	public function testAProviderOutsideTheListIsRejected(): void
	{
		$config = new AddressLookupConfig([
			'provider' => 'postcodeanywhere',
		]);

		self::assertFalse($config->validate());
		self::assertArrayHasKey('provider', $config->getErrors());
	}

	public function testLoqateIsAccepted(): void
	{
		$config = new AddressLookupConfig([
			'provider' => AddressLookupConfig::PROVIDER_LOQATE,
			'apiKey' => '$FC_ADDRESS_LOOKUP_KEY',
		]);

		self::assertTrue($config->validate());
	}
}
