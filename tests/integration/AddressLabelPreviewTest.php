<?php

namespace fostercommerce\fostercheckout\tests\integration;

use craft\elements\Address;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * @since 1.6.0
 */
final class AddressLabelPreviewTest extends CartTestCase
{
	public function testTheLabelIsLeftOutUntilTheSettingIsTurnedOn(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$named = $this->firstNamedAddress($book);
		$settings = $this->settings();
		$original = $settings->showAddressLabelInPreview;
		$settings->showAddressLabelInPreview = false;

		try {
			$label = $this->checkout()->checkoutLiveState($cart)['addressLabels'][(int) $named->id];

			self::assertStringStartsNotWith(trim((string) $named->title) . ',', $label);
		} finally {
			$settings->showAddressLabelInPreview = $original;
		}
	}

	public function testABookAddressIsNamedByItsLabel(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$named = $this->firstNamedAddress($book);
		$settings = $this->settings();
		$original = $settings->showAddressLabelInPreview;
		$settings->showAddressLabelInPreview = true;

		try {
			$label = $this->checkout()->checkoutLiveState($cart)['addressLabels'][(int) $named->id];

			self::assertStringStartsWith(trim((string) $named->title) . ',', $label);
		} finally {
			$settings->showAddressLabelInPreview = $original;
		}
	}

	/**
	 * Commerce titles an order's own addresses after the slot they fill, so those titles are not names.
	 */
	public function testAnOrderAddressIsNotNamedByItsTitle(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$settings = $this->settings();
		$original = $settings->showAddressLabelInPreview;
		$settings->showAddressLabelInPreview = true;

		try {
			$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
				'shippingAddressId' => (string) $book[0]->id,
				'billingAddressSameAsShipping' => '0',
			]));

			$orderAddress = $cart->getShippingAddress();
			self::assertInstanceOf(Address::class, $orderAddress);

			$preview = $this->checkout()->checkoutLiveState($cart)['shippingPreview'];

			self::assertNotSame('', $preview);
			self::assertStringStartsNotWith(trim((string) $orderAddress->title) . ',', $preview);
		} finally {
			$settings->showAddressLabelInPreview = $original;
		}
	}

	/**
	 * The store location is nobody's saved address, so it has no name a customer chose.
	 */
	public function testThePickupLocationIsNotNamedByItsTitle(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		if (! $this->checkout()->customerPickupAvailable($cart)) {
			self::markTestSkipped('Customer pickup is not enabled on this site.');
		}

		$settings = $this->settings();
		$original = $settings->showAddressLabelInPreview;
		$settings->showAddressLabelInPreview = true;

		try {
			$preview = $this->checkout()->customerPickupPreview($cart);
			$settings->showAddressLabelInPreview = false;

			self::assertSame($this->checkout()->customerPickupPreview($cart), $preview);
		} finally {
			$settings->showAddressLabelInPreview = $original;
		}
	}

	/**
	 * @param list<Address> $book
	 */
	private function firstNamedAddress(array $book): Address
	{
		foreach ($book as $address) {
			if (trim((string) $address->title) !== '') {
				return $address;
			}
		}

		self::markTestSkipped('No address in this customer’s book has a name.');
	}
}
