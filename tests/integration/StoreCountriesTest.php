<?php

namespace fostercommerce\fostercheckout\tests\integration;

use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class StoreCountriesTest extends CheckoutTestCase
{
	public function testTheStoreSellsToAtLeastOneCountry(): void
	{
		$countries = $this->checkout()->storeCountries();

		self::assertNotSame([], $countries, 'The address form would render an empty country select.');
	}

	public function testRequiredFieldsAreKnownForEveryCountryTheStoreSellsTo(): void
	{
		$countries = array_keys($this->checkout()->storeCountries());
		$required = $this->checkout()->addressRequiredFields();

		self::assertSame($countries, array_keys($required));

		foreach ($required as $countryCode => $fields) {
			self::assertIsList($fields, "{$countryCode} required fields are not a list");
		}
	}

	public function testUsedFieldsAreKnownForEveryCountryTheStoreSellsTo(): void
	{
		$countries = array_keys($this->checkout()->storeCountries());

		self::assertSame($countries, array_keys($this->checkout()->addressUsedFields()));
	}

	/**
	 * Add it back through the used-fields event, since the GB format leaves it out.
	 */
	public function testGreatBritainKeepsItsCounty(): void
	{
		$usedFields = $this->checkout()->addressUsedFields();

		if (! array_key_exists('GB', $usedFields)) {
			self::markTestSkipped('This store does not sell to Great Britain.');
		}

		self::assertContains('administrativeArea', $usedFields['GB']);
	}

	public function testTheListsAreBuiltOnceAndReused(): void
	{
		self::assertSame($this->checkout()->addressRequiredFields(), $this->checkout()->addressRequiredFields());
		self::assertSame($this->checkout()->addressUsedFields(), $this->checkout()->addressUsedFields());
	}
}
