<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\elements\Address;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * Resolve a posted address id against the order's customer, not the signed-in user.
 *
 * @since 1.1.0
 */
final class CustomerAddressesTest extends CartTestCase
{
	public function testASavedAddressIsCopiedOntoTheOrder(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$chosen = $book[0];

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $chosen->id,
			'billingAddressSameAsShipping' => '0',
		]));

		self::assertSame($chosen->id, $cart->sourceShippingAddressId);
		self::assertNotSame($chosen->id, $cart->getShippingAddress()?->id, 'The order was given the book address itself');
		self::assertSame($chosen->addressLine1, $cart->getShippingAddress()?->addressLine1);
	}

	public function testRepostingTheSameAddressChangesNothing(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$params = [
			'shippingAddressId' => (string) $book[0]->id,
			'billingAddressSameAsShipping' => '0',
		];

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest($params));
		$applied = $cart->getShippingAddress()?->id;
		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest($params));

		self::assertSame($applied, $cart->getShippingAddress()?->id);
	}

	public function testSameAsShippingGivesBillingItsOwnCopy(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $book[0]->id,
			'billingAddressSameAsShipping' => '1',
		]));

		self::assertSame($book[0]->id, $cart->sourceBillingAddressId);
		self::assertNotSame($cart->getShippingAddress()?->id, $cart->getBillingAddress()?->id);

		Craft::$app->getElements()->saveElement($cart, false);

		self::assertTrue($cart->hasMatchingAddresses());
	}

	public function testMirroringTwiceChangesNothing(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$params = [
			'shippingAddressId' => (string) $book[0]->id,
			'billingAddressSameAsShipping' => '1',
		];

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest($params));
		$mirrored = $cart->getBillingAddress()?->id;
		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest($params));

		self::assertSame($mirrored, $cart->getBillingAddress()?->id);
	}

	public function testAnAddressOutsideTheCustomersBookIsRefused(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$bookIds = array_map(static fn (Address $address): int => (int) $address->id, $book);
		$foreign = Address::find()->id(array_merge(['not'], $bookIds))->one();

		if (! $foreign instanceof Address) {
			self::markTestSkipped('This site has no address outside the test customer’s book.');
		}

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $foreign->id,
			'billingAddressSameAsShipping' => '0',
		]));

		self::assertNull($cart->sourceShippingAddressId);
	}

	public function testACartUpdateWithNoAddressParamsLeavesTheOrderAlone(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $book[0]->id,
			'billingAddressSameAsShipping' => '0',
		]));
		$applied = $cart->sourceShippingAddressId;

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'qty' => '2',
		]));

		self::assertSame($applied, $cart->sourceShippingAddressId);
	}

	/**
	 * That path resolves the pair elsewhere, so touching either address here would undo it.
	 */
	public function testShippingSameAsBillingLeavesBothAddressesAlone(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $book[0]->id,
			'shippingAddressSameAsBilling' => '1',
		]));

		self::assertNull($cart->sourceShippingAddressId);
	}

	public function testAGuestCartIsLeftAlone(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		Craft::$app->getUser()->setIdentity(null);

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'shippingAddressId' => (string) $book[0]->id,
			'billingAddressSameAsShipping' => '0',
		]));

		self::assertNull($cart->sourceShippingAddressId);
	}

	public function testABillingAddressIsChosenOnItsOwn(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();

		$this->checkout()->applyCustomerAddresses($cart, $this->cartUpdateRequest([
			'billingAddressId' => (string) $book[0]->id,
		]));

		self::assertSame($book[0]->id, $cart->sourceBillingAddressId);
		self::assertSame($book[0]->addressLine1, $cart->getBillingAddress()?->addressLine1);
		self::assertNull($cart->sourceShippingAddressId);
	}

	public function testAnAddressTheOrderUsesIsOfferedPastTheLimit(): void
	{
		$settings = $this->settings();
		$originalLimit = $settings->savedAddressLimit;
		$settings->savedAddressLimit = 1;

		try {
			[, $cart, $book] = $this->signedInCustomerCart();

			if (count($book) < 2) {
				self::markTestSkipped('The customer needs two saved addresses for one to fall outside the limit.');
			}

			// A new service each time, since the list is cached per customer for the request
			$limitedIds = array_map(static fn (Address $address): ?int => $address->id, (new Checkout())->customerAddresses($cart));
			$outsideLimit = array_values(array_filter($book, static fn (Address $address): bool => ! in_array($address->id, $limitedIds, true)))[0];
			$cart->sourceBillingAddressId = $outsideLimit->id;

			$offeredIds = array_map(static fn (Address $address): ?int => $address->id, (new Checkout())->customerAddresses($cart));

			self::assertContains($outsideLimit->id, $offeredIds);
			self::assertCount(count($limitedIds) + 1, $offeredIds);
		} finally {
			$settings->savedAddressLimit = $originalLimit;
		}
	}

	public function testAnAddressTheCustomerDoesNotOwnIsNotOffered(): void
	{
		[, $cart, $book] = $this->signedInCustomerCart();
		$bookIds = array_map(static fn (Address $address): ?int => $address->id, $book);
		$otherAddress = Address::find()->id(['not', ...$bookIds])->one();

		if (! $otherAddress instanceof Address) {
			self::markTestSkipped("No address outside the customer's book exists on this site.");
		}

		$cart->sourceBillingAddressId = $otherAddress->id;
		$offeredIds = array_map(static fn (Address $address): ?int => $address->id, (new Checkout())->customerAddresses($cart));

		self::assertNotContains($otherAddress->id, $offeredIds);
	}
}
