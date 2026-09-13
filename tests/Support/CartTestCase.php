<?php

namespace fostercommerce\fostercheckout\tests\Support;

use Craft;
use craft\commerce\elements\Order;
use craft\elements\Address;
use craft\elements\User;
use yii\db\Transaction;

/**
 * Base for tests that save carts and addresses. Every write is rolled back.
 */
abstract class CartTestCase extends CheckoutTestCase
{
	private ?Transaction $transaction = null;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->plugin();
		$this->transaction = Craft::$app->getDb()->beginTransaction();
	}

	#[\Override]
	protected function tearDown(): void
	{
		$this->transaction?->rollBack();
		$this->transaction = null;

		parent::tearDown();
	}

	/**
	 * The customer the browser suites use, or any other user with a saved address book.
	 */
	protected function customerWithAddressBook(): User
	{
		$testUser = User::find()->email('claude-checkout-test@fostercommerce.com')->one();

		if ($testUser instanceof User && $testUser->getAddresses()->all() !== []) {
			return $testUser;
		}

		foreach (Address::find()->limit(200)->all() as $address) {
			$owner = $address->getOwner();

			if ($owner instanceof User && $owner->getAddresses()->all() !== []) {
				return $owner;
			}
		}

		self::markTestSkipped('No user on this site has a saved address book.');
	}

	/**
	 * @return list<Address>
	 */
	protected function addressBookOf(User $customer): array
	{
		/** @var list<Address> $addresses */
		$addresses = array_values($customer->getAddresses()->all());

		return $addresses;
	}

	protected function saveCartFor(User $customer): Order
	{
		$commerce = $this->commerce();

		$cart = new Order([
			'number' => $commerce->getCarts()->generateCartNumber(),
			'orderSiteId' => Craft::$app->getSites()->getPrimarySite()->id,
			'storeId' => $commerce->getStores()->getCurrentStore()->id,
		]);
		$cart->setCustomer($customer);

		self::assertTrue(
			Craft::$app->getElements()->saveElement($cart, false),
			'The test cart could not be saved: ' . json_encode($cart->getErrors())
		);

		return $cart;
	}

	/**
	 * Sign the customer in and return a cart of theirs, since the checkout reads both.
	 *
	 * @return array{0: User, 1: Order, 2: list<Address>}
	 */
	protected function signedInCustomerCart(): array
	{
		$customer = $this->customerWithAddressBook();
		Craft::$app->getUser()->setIdentity($customer);

		return [$customer, $this->saveCartFor($customer), $this->addressBookOf($customer)];
	}
}
