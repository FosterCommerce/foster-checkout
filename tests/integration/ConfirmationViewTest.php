<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\db\Query;
use craft\elements\User;
use fostercommerce\fostercheckout\db\Table;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * Whether each order's confirmation view can be claimed once.
 */
final class ConfirmationViewTest extends CartTestCase
{
	public function testTheFirstClaimForAnOrderSucceeds(): void
	{
		$order = $this->saveCartFor($this->guestCustomer());

		self::assertTrue($this->checkout()->claimConfirmationView($order));
	}

	public function testEveryLaterClaimForTheSameOrderFails(): void
	{
		$order = $this->saveCartFor($this->guestCustomer());
		$this->checkout()->claimConfirmationView($order);

		self::assertFalse($this->checkout()->claimConfirmationView($order));
		self::assertFalse($this->checkout()->claimConfirmationView($order));
	}

	public function testEachOrderHasItsOwnFirstView(): void
	{
		$customer = $this->guestCustomer();
		$firstOrder = $this->saveCartFor($customer);
		$secondOrder = $this->saveCartFor($customer);
		$this->checkout()->claimConfirmationView($firstOrder);

		self::assertTrue($this->checkout()->claimConfirmationView($secondOrder));
	}

	public function testDeletingTheOrderRemovesItsClaim(): void
	{
		$order = $this->saveCartFor($this->guestCustomer());
		$this->checkout()->claimConfirmationView($order);

		Craft::$app->getElements()->deleteElement($order, true);

		self::assertFalse(
			(new Query())
				->from(Table::CONFIRMATION_VIEWS)
				->where([
					'orderId' => $order->id,
				])
				->exists()
		);
	}

	/**
	 * Any user works, since the claim reads only the order.
	 */
	private function guestCustomer(): User
	{
		return Craft::$app->getUsers()->ensureUserByEmail('confirmation-view-test@example.com');
	}
}
