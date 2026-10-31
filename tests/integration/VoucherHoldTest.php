<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\commerce\db\Table as CommerceTable;
use craft\commerce\elements\Order;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\db\Query;
use craft\helpers\Db;
use DateTime;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;
use verbb\giftvoucher\elements\Code;
use verbb\giftvoucher\records\Reservation;
use verbb\giftvoucher\services\Reservations;

/**
 * Uses the site's existing carts and voucher codes rather than saving new ones, so only hold rows are written.
 *
 * @since 2.1.0
 */
final class VoucherHoldTest extends CartTestCase
{
	#[\Override]
	protected function setUp(): void
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('gift-voucher') || ! class_exists(Reservation::class)) {
			self::markTestSkipped('Gift Voucher 4.0.20 or later is not enabled on this site.');
		}

		parent::setUp();
	}

	public function testAPaymentReleasesTheOrdersHoldFromAnEarlierAttempt(): void
	{
		[$cart] = $this->cartsWithoutLivePayments(1);
		$codeId = $this->codeId();
		$holdId = $this->hold($cart, $codeId);

		$this->checkout()->releaseStaleVoucherHolds($cart);

		self::assertSame(Reservations::STATUS_RELEASED, $this->holdStatus($holdId));
	}

	public function testApplyingACodeReleasesAnotherCartsIdleHoldOnIt(): void
	{
		[$cart, $abandonedCart] = $this->cartsWithoutLivePayments(2);
		$codeId = $this->codeId();
		$holdId = $this->hold($abandonedCart, $codeId, minutesIdle: 20);

		$this->checkout()->releaseStaleVoucherHolds($cart, $this->codeKey($codeId));

		self::assertSame(Reservations::STATUS_RELEASED, $this->holdStatus($holdId));
		self::assertNull($this->checkout()->voucherHoldMinutesLeft($cart, $this->codeKey($codeId)));
	}

	public function testApplyingACodeReleasesTheIdleHoldOfACheckoutWithOnlyARedirectPayment(): void
	{
		[$cart, $abandonedCart] = $this->cartsWithoutLivePayments(2);
		$codeId = $this->codeId();
		$holdId = $this->hold($abandonedCart, $codeId, minutesIdle: 20);
		Db::insert(CommerceTable::TRANSACTIONS, [
			'orderId' => $abandonedCart->id,
			'hash' => 'test-redirect-transaction',
			'type' => TransactionRecord::TYPE_PURCHASE,
			'status' => TransactionRecord::STATUS_REDIRECT,
		]);

		$this->checkout()->releaseStaleVoucherHolds($cart, $this->codeKey($codeId));

		self::assertSame(Reservations::STATUS_RELEASED, $this->holdStatus($holdId));
	}

	public function testApplyingACodeKeepsAnotherCartsRecentHoldAndReportsTheWait(): void
	{
		[$cart, $payingCart] = $this->cartsWithoutLivePayments(2);
		$codeId = $this->codeId();
		$holdId = $this->hold($payingCart, $codeId, minutesIdle: 5);

		$this->checkout()->releaseStaleVoucherHolds($cart, $this->codeKey($codeId));

		self::assertSame(Reservations::STATUS_HELD, $this->holdStatus($holdId));
		self::assertSame(10, $this->checkout()->voucherHoldMinutesLeft($cart, $this->codeKey($codeId)));
	}

	public function testApplyingACodeKeepsTheHoldOfACompletedOrder(): void
	{
		[$cart] = $this->cartsWithoutLivePayments(1);

		/** @var Order|null $completedOrder */
		$completedOrder = Order::find()->isCompleted(true)->one();

		if (! $completedOrder instanceof Order) {
			self::markTestSkipped('This site has no completed order.');
		}

		$codeId = $this->codeId();
		$holdId = $this->hold($completedOrder, $codeId, minutesIdle: 20);

		$this->checkout()->releaseStaleVoucherHolds($cart, $this->codeKey($codeId));

		self::assertSame(Reservations::STATUS_HELD, $this->holdStatus($holdId));
	}

	public function testAPaymentKeepsTheOrdersRecentHoldWithoutATransaction(): void
	{
		[$cart] = $this->cartsWithoutLivePayments(1);
		$holdId = $this->hold($cart, $this->codeId(), null);

		$this->checkout()->releaseStaleVoucherHolds($cart);

		self::assertSame(Reservations::STATUS_HELD, $this->holdStatus($holdId));
	}

	public function testAPaymentReleasesTheOrdersIdleHoldWithoutATransaction(): void
	{
		[$cart] = $this->cartsWithoutLivePayments(1);
		$holdId = $this->hold($cart, $this->codeId(), null, 20);

		$this->checkout()->releaseStaleVoucherHolds($cart);

		self::assertSame(Reservations::STATUS_RELEASED, $this->holdStatus($holdId));
	}

	public function testApplyingACodeLeavesHoldsOnOtherCodesAlone(): void
	{
		[$cart, $otherCart] = $this->cartsWithoutLivePayments(2);
		[$appliedCodeId, $otherCodeId] = $this->codeIds(2);
		$holdId = $this->hold($otherCart, $otherCodeId, minutesIdle: 20);

		$this->checkout()->releaseStaleVoucherHolds($cart, $this->codeKey($appliedCodeId));

		self::assertSame(Reservations::STATUS_HELD, $this->holdStatus($holdId));
	}

	/**
	 * @return list<Order>
	 */
	private function cartsWithoutLivePayments(int $count): array
	{
		$ordersWithLivePayments = (new Query())
			->select(['orderId'])
			->from(CommerceTable::TRANSACTIONS)
			->where([
				'status' => [TransactionRecord::STATUS_PENDING, TransactionRecord::STATUS_PROCESSING, TransactionRecord::STATUS_SUCCESS],
			]);

		/** @var list<Order> $carts */
		$carts = Order::find()
			->isCompleted(false)
			->andWhere(['not in', 'commerce_orders.id', $ordersWithLivePayments])
			->limit($count)
			->all();

		if (count($carts) < $count) {
			self::markTestSkipped("This site has fewer than {$count} carts without a payment.");
		}

		return $carts;
	}

	private function codeId(): int
	{
		return $this->codeIds(1)[0];
	}

	/**
	 * @return list<int>
	 */
	private function codeIds(int $count): array
	{
		// Pick live codes with value and no holds, since a hold is unique per code and order
		/** @var list<int|string> $codeIds */
		$codeIds = Code::find()
			->andWhere(['>', 'giftvoucher_codes.currentAmount', 0])
			->andWhere(['not in', 'giftvoucher_codes.id', (new Query())->select(['codeId'])->from(Reservation::tableName())])
			->andWhere([
				'or',
				[
					'giftvoucher_codes.expiryDate' => null,
				],
				['>=', 'giftvoucher_codes.expiryDate', Db::prepareDateForDb(new DateTime('tomorrow'))],
			])
			->limit($count)
			->ids();

		if (count($codeIds) < $count) {
			self::markTestSkipped("This site has fewer than {$count} gift voucher codes with value and no holds.");
		}

		return array_map(intval(...), $codeIds);
	}

	private function codeKey(int $codeId): string
	{
		/** @var string $codeKey */
		$codeKey = (new Query())
			->select(['codeKey'])
			->from('{{%giftvoucher_codes}}')
			->where([
				'id' => $codeId,
			])
			->scalar();

		return $codeKey;
	}

	private function hold(Order $order, int $codeId, ?string $transactionHash = 'test-transaction-hash', int $minutesIdle = 0): int
	{
		$hold = new Reservation();
		$hold->setAttributes([
			'codeId' => $codeId,
			'orderId' => $order->id,
			'orderNumber' => $order->number,
			'amount' => (new Query())->select(['currentAmount'])->from('{{%giftvoucher_codes}}')->where([
				'id' => $codeId,
			])->scalar(),
			'status' => Reservations::STATUS_HELD,
			'transactionHash' => $transactionHash,
		], false);
		$hold->save(false);

		/** @var int|string $holdId */
		$holdId = $hold->getPrimaryKey();

		Db::update(Reservation::tableName(), [
			'dateUpdated' => Db::prepareDateForDb(new DateTime("-{$minutesIdle} minutes")),
		], [
			'id' => $holdId,
		]);

		return (int) $holdId;
	}

	private function holdStatus(int $holdId): string
	{
		/** @var string $status */
		$status = (new Query())
			->select(['status'])
			->from(Reservation::tableName())
			->where([
				'id' => $holdId,
			])
			->scalar();

		return $status;
	}
}
