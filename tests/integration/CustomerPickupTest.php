<?php

namespace fostercommerce\fostercheckout\tests\integration;

use craft\elements\Address;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * @since 1.4.0
 */
final class CustomerPickupTest extends CartTestCase
{
	public function testPickupIsOffUntilTheSettingIsTurnedOn(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$settings = $this->settings();
		$original = $settings->enableCustomerPickup;
		$settings->enableCustomerPickup = false;

		try {
			self::assertFalse($this->checkout()->customerPickupAvailable($cart));
			self::assertFalse($this->checkout()->isCustomerPickup($cart));
		} finally {
			$settings->enableCustomerPickup = $original;
		}
	}

	/**
	 * A store location always exists, so only a filled street means pickup is possible.
	 */
	public function testPickupNeedsAStoreLocationWithAStreet(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$settings = $this->settings();
		$original = $settings->enableCustomerPickup;
		$settings->enableCustomerPickup = true;

		try {
			$hasLocation = trim((string) $this->storeLocation()?->addressLine1) !== '';

			self::assertSame($hasLocation, $this->checkout()->customerPickupAvailable($cart));
		} finally {
			$settings->enableCustomerPickup = $original;
		}
	}

	public function testABlankLabelFallsBackToTheTranslatedDefault(): void
	{
		$settings = $this->settings();
		$original = $settings->customerPickupLabel;
		$settings->customerPickupLabel = null;

		try {
			self::assertNotSame('', $this->checkout()->customerPickupLabel());
		} finally {
			$settings->customerPickupLabel = $original;
		}
	}

	public function testAConfiguredLabelIsUsed(): void
	{
		$settings = $this->settings();
		$original = $settings->customerPickupLabel;
		$settings->customerPickupLabel = 'Collect in store';

		try {
			self::assertSame('Collect in store', $this->checkout()->customerPickupLabel());
		} finally {
			$settings->customerPickupLabel = $original;
		}
	}

	public function testChoosingPickupShipsTheOrderToTheStoreLocation(): void
	{
		$this->requirePickup();
		[, $cart] = $this->signedInCustomerCart();
		$location = $this->storeLocation();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '1',
		]));

		self::assertSame($location?->addressLine1, $cart->getShippingAddress()?->addressLine1);
		self::assertTrue($this->checkout()->isCustomerPickup($cart));
		self::assertTrue($this->checkout()->checkoutLiveState($cart)['customerPickup']);
	}

	/**
	 * No pickup flag is stored on the order, so the address itself has to say so.
	 */
	public function testTheCollectorIsNamedOnThePickupAddress(): void
	{
		$this->requirePickup();
		[$customer, $cart] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '1',
		]));

		self::assertSame(trim((string) $customer->fullName), trim((string) $cart->getShippingAddress()?->fullName));
	}

	public function testChoosingPickupTwiceChangesNothing(): void
	{
		$this->requirePickup();
		[, $cart] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '1',
		]));
		$applied = $cart->getShippingAddress()?->id;
		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '1',
		]));

		self::assertSame($applied, $cart->getShippingAddress()?->id);
	}

	public function testTurningPickupOffClearsTheStoreLocation(): void
	{
		$this->requirePickup();
		[, $cart] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '1',
		]));
		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '0',
		]));

		self::assertNull($cart->getShippingAddress());
		self::assertFalse($this->checkout()->isCustomerPickup($cart));
	}

	public function testPickupDoesNotMirrorTheStoreLocationOntoBilling(): void
	{
		$this->requirePickup();
		[, $cart] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingPickup' => '1',
			'billingAddressSameAsShipping' => '1',
		]));

		self::assertNotSame(
			$this->storeLocation()?->addressLine1,
			$cart->getBillingAddress()?->addressLine1
		);
	}

	public function testAnOrderShippedElsewhereIsNotAPickup(): void
	{
		$this->requirePickup();
		[, $cart, $book] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $book[0]->id,
			'billingAddressSameAsShipping' => '0',
		]));

		self::assertFalse($this->checkout()->isCustomerPickup($cart));
	}

	private function storeLocation(): ?Address
	{
		return $this->commerce()->getStores()->getCurrentStore()->getSettings()->getLocationAddress();
	}

	private function requirePickup(): void
	{
		if (! $this->settings()->enableCustomerPickup || trim((string) $this->storeLocation()?->addressLine1) === '') {
			self::markTestSkipped('Customer pickup is not enabled on this site.');
		}
	}
}
