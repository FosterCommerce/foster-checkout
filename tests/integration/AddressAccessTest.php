<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\commerce\elements\Order;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * @since 1.1.0
 */
final class AddressAccessTest extends CartTestCase
{
	public function testAGuestIsOfferedNoAddressBook(): void
	{
		Craft::$app->getUser()->setIdentity(null);
		$cart = new Order();

		self::assertFalse($this->checkout()->canViewAddresses($cart));
		self::assertFalse($this->checkout()->canSaveAddresses($cart));
	}

	public function testAGuestGetsNoAddressLabels(): void
	{
		Craft::$app->getUser()->setIdentity(null);

		self::assertSame([], $this->checkout()->checkoutLiveState(new Order())['addressLabels']);
	}

	public function testACustomerMaySeeTheirOwnBook(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertTrue($this->checkout()->canViewAddresses($cart));
	}

	public function testTheLabelsCoverTheWholeBook(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();

		self::assertCount(count($book), $this->checkout()->checkoutLiveState($cart)['addressLabels']);
	}

	/**
	 * The template's own fallback builds the same string, so the two must not diverge.
	 */
	public function testALabelIsTheFullNameFollowedByTheFormattedAddress(): void
	{
		$settings = $this->settings();
		$original = $settings->showAddressLabelInPreview;
		$settings->showAddressLabelInPreview = false;

		try {
			[, $cart, $book] = $this->signedInCustomerCart();
			$labels = $this->checkout()->checkoutLiveState($cart)['addressLabels'];
			$formatter = $this->checkout()->addressFormatter();

			foreach ($book as $address) {
				$expected = implode(', ', array_filter([
					$address->fullName,
					Craft::$app->getAddresses()->formatAddress($address, [], $formatter),
				]));

				self::assertSame($expected, $labels[(int) $address->id] ?? null);
			}
		} finally {
			$settings->showAddressLabelInPreview = $original;
		}
	}

	/**
	 * Our formatter prints the country name, so a trailing two letter code means it was not used.
	 */
	public function testNoLabelEndsInABareCountryCode(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		foreach ($this->checkout()->checkoutLiveState($cart)['addressLabels'] as $label) {
			self::assertDoesNotMatchRegularExpression('/, [A-Z]{2}$/', $label);
		}
	}
}
