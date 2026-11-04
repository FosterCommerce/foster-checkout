<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\commerce\base\Gateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\elements\Order;
use craft\commerce\events\ProcessPaymentEvent;
use craft\commerce\events\TransactionEvent;
use craft\commerce\helpers\Currency;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\commerce\services\Payments;
use craft\commerce\services\Transactions;
use craft\helpers\Json;
use fostercommerce\fostercheckout\events\DefinePartialPaymentEvent;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\PartialPaymentStep;
use fostercommerce\netterms\enums\EntryStatus;
use fostercommerce\netterms\gateways\NetTerms;
use fostercommerce\netterms\models\responses\NetTermsResponse;
use fostercommerce\netterms\Plugin as NetTermsPlugin;
use RuntimeException;
use Throwable;
use yii\base\Component;

/**
 * @internal
 * @since 2.1.0
 */
class PartialPayments extends Component
{
	public const string MARKER = '_fosterCheckoutPartialPayment';

	/**
	 * @var array<int, true>
	 */
	private static array $cancelledPayments = [];

	public function paymentNeedsRefresh(Order $order): bool
	{
		return isset(self::$cancelledPayments[(int) $order->id]);
	}

	public function configured(Gateway $gateway): bool
	{
		return $this->checkout()->settings()->paymentGateways[$gateway->handle]->useAsPartialPaymentStep ?? false;
	}

	/**
	 * @return list<PartialPaymentStep>
	 */
	public function steps(Order $order): array
	{
		$steps = [];
		foreach ($order->getTransactions() as $transaction) {
			$applied = $this->coverage($transaction);
			$gateway = $transaction->getGateway();
			if ($applied <= 0) {
				continue;
			}

			if (! $gateway instanceof NetTerms) {
				continue;
			}

			$entry = $transaction->type === TransactionRecord::TYPE_AUTHORIZE
				? $gateway->getPartialAuthorization($transaction)
				: NetTermsPlugin::getInstance()->ledger->getChargeByTransactionHash((string) $transaction->hash);
			$account = $entry?->accountId ? NetTermsPlugin::getInstance()->accounts->getFreshAccountById($entry->accountId) : null;
			$definition = $this->definition($order, $gateway);
			$steps[] = new PartialPaymentStep([
				'gateway' => $gateway,
				'applied' => $applied,
				'accountId' => $entry?->accountId,
				'accountName' => $account?->title ?? $this->checkout()->gatewayLabel($gateway),
				'transactionId' => $transaction->id,
				'summaryLabel' => $definition->summaryLabel ?? $this->checkout()->gatewayLabel($gateway),
				'label' => $definition->label,
				'appliedLabel' => $definition->appliedLabel,
			]);
		}

		foreach ($order->getAvailableGateways() as $gateway) {
			if (! $gateway instanceof NetTerms) {
				continue;
			}

			if (! $this->supported($gateway)) {
				continue;
			}

			$definition = $this->definition($order, $gateway);
			$target = min($this->remainingAmount($order), max(0.0, ($definition->amount ?? 0.0) - $this->appliedAmount($order, (int) $gateway->id)));
			$selection = $gateway->partialPayment($order, $target);
			if ($selection['amount'] > 0) {
				$steps[] = new PartialPaymentStep([
					'gateway' => $gateway,
					'amount' => $selection['amount'],
					'accountId' => $selection['accountId'],
					'summaryLabel' => $definition->summaryLabel ?? $this->checkout()->gatewayLabel($gateway),
					'label' => $definition->label,
					'appliedLabel' => $definition->appliedLabel,
				]);
			}
		}

		return $steps;
	}

	public function hasApplied(Order $order): bool
	{
		return $this->appliedAmount($order) > 0;
	}

	public function awaitingBalance(Order $order): bool
	{
		return ! $order->isCompleted && $this->remainingAmount($order) > 0 && $this->hasApplied($order);
	}

	public function hasPurchasedPayment(Order $order): bool
	{
		return collect($order->getTransactions())->contains(fn (Transaction $transaction): bool => $transaction->type === TransactionRecord::TYPE_PURCHASE && $this->coverage($transaction) > 0);
	}

	public function appliedAmount(Order $order, ?int $gatewayId = null): float
	{
		/** @var float $amount */
		$amount = collect($order->getTransactions())
			->filter(static fn (Transaction $transaction): bool => $gatewayId === null || (int) $transaction->gatewayId === $gatewayId)
			->sum(fn (Transaction $transaction): float => $this->coverage($transaction));

		return $amount;
	}

	public function authorizedAmount(Order $order): float
	{
		/** @var float $amount */
		$amount = collect($this->authorizations($order))->sum(fn (Transaction $transaction): float => $this->coverage($transaction));

		return $amount;
	}

	public function remainingAmount(Order $order): float
	{
		return Currency::round(max(0.0, $order->getOutstandingBalance() - $this->authorizedAmount($order)), $order->currency);
	}

	public static function isPartialTransaction(Transaction $transaction): bool
	{
		$response = is_string($transaction->response) ? Json::decodeIfJson($transaction->response) : $transaction->response;

		return is_array($response) && ($response[self::MARKER] ?? false) === true;
	}

	public function appliedAmountsMatch(Order $order): bool
	{
		return collect($this->steps($order))->every(fn (PartialPaymentStep $step): bool => $step->applied <= Currency::round(min($this->definition($order, $step->gateway)->amount ?? 0.0, $order->getTotalPrice()), $order->currency));
	}

	public function reconcile(Order $order): void
	{
		if ($order->isCompleted) {
			return;
		}

		$remaining = $order->getOutstandingBalance();
		$gatewayAmounts = [];
		foreach ($this->authorizations($order) as $transaction) {
			$gateway = $transaction->getGateway();
			if (! $gateway instanceof NetTerms) {
				continue;
			}

			if ($gateway->getPartialAuthorization($transaction)?->status !== EntryStatus::Authorized) {
				continue;
			}

			$target = Currency::round(max(0.0, min(($this->definition($order, $gateway)->amount ?? 0.0) - ($gatewayAmounts[(int) $gateway->id] ?? 0.0), $remaining)), $order->currency);
			if ($target !== $transaction->amount) {
				$this->requireNoPendingPayment($order);
				$gateway->withPartialRefundLock($transaction, function () use ($gateway, $transaction, $target): void {
					$amount = $gateway->resizeAuthorization($transaction, $target);
					$transaction->amount = $amount;

					$transaction->paymentAmount = Currency::round($amount * $transaction->paymentRate, $transaction->paymentCurrency);
					$transaction->message = $amount > 0 ? Craft::t('foster-checkout', 'partialPayment.authorizationUpdated') : Craft::t('foster-checkout', 'partialPayment.authorizationReleasedAfterChanges');
					if ($amount <= 0) {
						$transaction->status = TransactionRecord::STATUS_FAILED;
					}

					$this->persist($transaction);
				});
			}

			$remaining -= $transaction->amount;
			$gatewayAmounts[(int) $gateway->id] = ($gatewayAmounts[(int) $gateway->id] ?? 0.0) + $transaction->amount;
		}

		$order->setTransactions(null);
	}

	public function capture(Order $order): void
	{
		if (! $order->isCompleted) {
			return;
		}

		foreach ($this->authorizations($order) as $transaction) {
			$gateway = $transaction->getGateway();
			if (! $gateway instanceof NetTerms) {
				continue;
			}

			$capture = $this->commerce()->getTransactions()->createTransaction(null, $transaction, TransactionRecord::TYPE_CAPTURE);
			$capture->setOrder($order);
			$capture->amount = $transaction->amount;
			$capture->paymentAmount = $transaction->paymentAmount;
			try {
				$gateway->withPartialRefundLock($transaction, function () use ($gateway, $transaction, $capture): void {
					if ($gateway->getPartialAuthorization($transaction)?->status !== EntryStatus::Authorized) {
						return;
					}

					$this->save($capture, $gateway->capture($capture, (string) $transaction->reference));
				});
			} catch (Throwable $exception) {
				$capture->id = null;
				$this->save($capture, NetTermsResponse::declined($exception->getMessage()));
				Craft::error('Unable to capture Net Terms authorization ' . $transaction->id . ': ' . $exception->getMessage(), __METHOD__);
			}
		}

		$order->setTransactions(null);
		$order->updateOrderPaidInformation();
	}

	public function amount(Order $order, Gateway $gateway, ?int $accountId = null): float
	{
		return $gateway instanceof NetTerms
			? $gateway->partialPayment($order, max(0.0, min($this->remainingAmount($order), ($this->definition($order, $gateway)->amount ?? 0.0) - $this->appliedAmount($order, (int) $gateway->id))), $accountId)['amount']
			: 0.0;
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function apply(Order $order, int $gatewayId, array $attributes, ?float $expectedAmount = null): void
	{
		$this->requireNoPendingPayment($order);
		$gateway = $this->commerce()->getGateways()->getGatewayById($gatewayId);
		if (! $gateway instanceof NetTerms || ! $this->supported($gateway) || ! $gateway->availableForUseWithOrder($order)) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.methodUnavailable'));
		}


		$originalTotal = $order->getTotalPrice();
		$originalQuantity = $order->getTotalQty();
		$order->setRecalculationMode(Order::RECALCULATION_MODE_ALL);
		$order->recalculate();
		if (! Craft::$app->getElements()->saveElement($order, true)) {
			throw new RuntimeException(implode(' ', $order->getFirstErrors()));
		}

		$block = $this->checkout()->paymentBlock($order);
		if ($block !== null) {
			throw new RuntimeException($block);
		}

		$this->validateCheckout($order);

		$amount = $this->amount($order, $gateway, is_numeric($attributes['accountId'] ?? null) ? (int) $attributes['accountId'] : null);
		if ($originalTotal !== $order->getTotalPrice() || $originalQuantity !== $order->getTotalQty()
			|| ($expectedAmount !== null && $expectedAmount !== $amount)) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.orderChanged'));
		}

		if ($amount <= 0) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.nothingToApply'));
		}

		$form = $gateway->getPaymentFormModel();
		$form->setAttributes([
			'accountId' => $attributes['accountId'] ?? null,
		], false);
		if (! $form->validate()) {
			throw new RuntimeException(implode(' ', $form->getFirstErrors()));
		}

		$order->setGatewayId($gatewayId);
		$order->paymentSourceId = null;
		$order->setPaymentCurrency((string) $order->currency);
		$order->setPaymentAmount($amount);
		$order->setRecalculationMode(Order::RECALCULATION_MODE_NONE);

		$payments = $this->commerce()->getPayments();
		$event = new ProcessPaymentEvent([
			'order' => $order,
			'form' => $form,
		]);
		$payments->trigger(Payments::EVENT_BEFORE_PROCESS_PAYMENT, $event);
		if (! $event->isValid) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.unableToApply'));
		}

		$transaction = $this->commerce()->getTransactions()->createTransaction($order, null, TransactionRecord::TYPE_AUTHORIZE);
		$response = $gateway->authorizePartial($transaction, $form);
		$this->save($transaction, $response);
		$payments->trigger(Payments::EVENT_AFTER_PROCESS_PAYMENT, new ProcessPaymentEvent([
			'order' => $order,
			'transaction' => $transaction,
			'form' => $form,
			'response' => $response,
		]));
		if ($transaction->status !== TransactionRecord::STATUS_SUCCESS) {
			throw new RuntimeException($transaction->message ?? Craft::t('foster-checkout', 'partialPayment.applyFailed'));
		}

		$order->setTransactions(null);
		if (! Craft::$app->getElements()->saveElement($order, false)) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.appliedSaveFailed'));
		}
	}

	public function remove(Order $order, int $gatewayId, ?int $transactionId = null): void
	{
		$this->requireNoPendingPayment($order);
		foreach ($this->authorizations($order) as $transaction) {
			if ((int) $transaction->gatewayId !== $gatewayId) {
				continue;
			}

			if ($transactionId !== null && $transaction->id !== $transactionId) {
				continue;
			}

			$gateway = $transaction->getGateway();
			if ($gateway instanceof NetTerms && $gateway->getPartialAuthorization($transaction)?->status === EntryStatus::Authorized) {
				$response = $gateway->releaseAuthorization($transaction);
				if (! $response->isSuccessful()) {
					throw new RuntimeException($response->getMessage() !== '' ? $response->getMessage() : Craft::t('foster-checkout', 'partialPayment.releaseFailed'));
				}

				$transaction->status = TransactionRecord::STATUS_FAILED;
				$transaction->message = Craft::t('foster-checkout', 'partialPayment.authorizationReleasedByCustomer');
				$this->persist($transaction);
			}
		}

		$purchases = collect($order->getTransactions())
			->filter(static fn (Transaction $transaction): bool => $transaction->type === TransactionRecord::TYPE_PURCHASE
				&& $transaction->status === TransactionRecord::STATUS_SUCCESS
				&& (int) $transaction->gatewayId === $gatewayId
				&& ($transactionId === null || $transaction->id === $transactionId)
				&& self::isPartialTransaction($transaction)
				&& $transaction->getRefundableAmount() > 0);

		foreach ($purchases as $purchase) {
			$gateway = $purchase->getGateway();
			if (! $gateway instanceof NetTerms) {
				throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.cannotRemove'));
			}

			$refund = $this->commerce()->getTransactions()->createTransaction(null, $purchase, TransactionRecord::TYPE_REFUND);
			$refund->paymentAmount = $purchase->getRefundableAmount();
			$refund->amount = Currency::round($refund->paymentAmount / $purchase->paymentRate, $purchase->currency);
			$refund->setOrder($order);
			$this->save($refund, $gateway->refund($refund));
			if ($refund->status !== TransactionRecord::STATUS_SUCCESS) {
				throw new RuntimeException($refund->message ?? Craft::t('foster-checkout', 'partialPayment.removeFailed'));
			}
		}

		$order->setTransactions(null);
		$order->setRecalculationMode(Order::RECALCULATION_MODE_NONE);
		if (! Craft::$app->getElements()->saveElement($order, false)) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.removedSaveFailed'));
		}
	}

	public function prepareCartChange(Order $order): void
	{
		$mutex = Craft::$app->getMutex();
		$lockName = 'order:' . $order->number;
		if (! $mutex->isAcquired($lockName)) {
			if (! $mutex->acquire($lockName, 5)) {
				throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.cartBusy'));
			}

			Craft::$app->on(\yii\base\Application::EVENT_AFTER_REQUEST, static function () use ($mutex, $lockName): void {
				$mutex->release($lockName);
			});
		}

		$order->setTransactions(null);
		$this->requireNoPendingPayment($order);
	}

	public function releaseAll(Order $order): void
	{
		foreach ($this->authorizations($order) as $transaction) {
			$gateway = $transaction->getGateway();
			if ($gateway instanceof NetTerms) {
				$gateway->withPartialRefundLock($transaction, function () use ($order, $transaction): void {
					$this->remove($order, (int) $transaction->gatewayId, $transaction->id);
				});
			}
		}
	}

	public function complete(Order $order): void
	{
		$this->requireNoPendingPayment($order);
		$order->setRecalculationMode(Order::RECALCULATION_MODE_ALL);
		if (! Craft::$app->getElements()->saveElement($order, true)) {
			throw new RuntimeException(implode(' ', $order->getFirstErrors()));
		}

		$this->reconcile($order);
		$this->validateCheckout($order);
		if (! $this->hasApplied($order) || $this->remainingAmount($order) > 0 || $this->checkout()->paymentBlock($order) !== null) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.reviewBeforeCompleting'));
		}

		$gateway = $order->getGateway();
		if (! $gateway instanceof Gateway) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.methodNoLongerAvailable'));
		}

		$event = new ProcessPaymentEvent([
			'order' => $order,
			'form' => $gateway->getPaymentFormModel(),
		]);
		$this->commerce()->getPayments()->trigger(Payments::EVENT_BEFORE_PROCESS_PAYMENT, $event);
		if (! $event->isValid) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.unableToComplete'));
		}

		$order->updateOrderPaidInformation();
		if (! $order->isCompleted) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.completeFailed'));
		}
	}

	/**
	 * @return list<Transaction>
	 */
	private function authorizations(Order $order): array
	{
		return collect($order->getTransactions())
			->filter(static fn (Transaction $transaction): bool => self::isPartialTransaction($transaction)
				&& $transaction->type === TransactionRecord::TYPE_AUTHORIZE
				&& $transaction->status === TransactionRecord::STATUS_SUCCESS)
			->values()->all();
	}

	private function coverage(Transaction $transaction): float
	{
		if (! self::isPartialTransaction($transaction) || $transaction->status !== TransactionRecord::STATUS_SUCCESS) {
			return 0.0;
		}

		if ($transaction->type === TransactionRecord::TYPE_AUTHORIZE) {
			$gateway = $transaction->getGateway();
			$entry = $gateway instanceof NetTerms ? $gateway->getPartialAuthorization($transaction) : null;
			return $entry?->status === EntryStatus::Authorized ? $transaction->amount : 0.0;
		}

		return in_array($transaction->type, [TransactionRecord::TYPE_PURCHASE, TransactionRecord::TYPE_CAPTURE], true)
			? $transaction->getRefundableAmount() / $transaction->paymentRate : 0.0;
	}

	private function validateCheckout(Order $order): void
	{
		if ($order->getLineItems() === [] || $order->email === null || $order->email === '' || ($order->getStore()->getRequireShippingAddressAtCheckout() && $order->shippingAddressId === null)
			|| ($order->getStore()->getRequireBillingAddressAtCheckout() && $order->billingAddressId === null)
			|| ($order->getStore()->getRequireShippingMethodSelectionAtCheckout() && ($order->shippingMethodHandle === null || $order->shippingMethodHandle === ''))) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.completeDetails'));
		}
	}

	private function definition(Order $order, Gateway $gateway): DefinePartialPaymentEvent
	{
		$event = new DefinePartialPaymentEvent([
			'order' => $order,
			'gateway' => $gateway,
		]);
		$this->checkout()->trigger(Checkout::EVENT_DEFINE_PARTIAL_PAYMENT, $event);

		if ($event->amount === null || ! is_finite($event->amount) || $event->amount <= 0) {
			$event->amount = 0.0;
		} else {
			$event->amount = Currency::round($event->amount, $order->currency);
		}

		return $event;
	}

	private function requireNoPendingPayment(Order $order): void
	{
		$transactions = collect($order->getTransactions());
		$cancelledParents = $transactions
			->filter(static function (Transaction $transaction): bool {
				$response = is_string($transaction->response) ? Json::decodeIfJson($transaction->response) : $transaction->response;

				return $transaction->parentId !== null && $transaction->status === TransactionRecord::STATUS_FAILED
					&& is_array($response) && ($response['object'] ?? null) === 'payment_intent' && ($response['status'] ?? null) === 'canceled';
			})
			->mapWithKeys(static fn (Transaction $transaction): array => [
				$transaction->parentId => $transaction->reference,
			]);
		$pending = $transactions
			->filter(static fn (Transaction $transaction): bool => ! self::isPartialTransaction($transaction)
				&& in_array($transaction->status, [TransactionRecord::STATUS_PENDING, TransactionRecord::STATUS_REDIRECT, TransactionRecord::STATUS_PROCESSING], true)
				&& (! $cancelledParents->has((int) $transaction->id) || $cancelledParents->get((int) $transaction->id) !== $transaction->reference));

		foreach ($pending as $transaction) {
			$gateway = $transaction->getGateway();
			if ($gateway instanceof \craft\commerce\stripe\gateways\PaymentIntents && $transaction->reference) {
				$intents = $gateway->getStripeClient()->paymentIntents;
				$intent = $intents->retrieve($transaction->reference);
				if (in_array($intent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
					$intent = $intents->cancel($transaction->reference);
				}

				if ($intent->status === 'canceled') {
					$cancelled = $this->commerce()->getTransactions()->createTransaction(null, $transaction);
					$cancelled->setOrder($order);
					$cancelled->status = TransactionRecord::STATUS_FAILED;
					$cancelled->message = Craft::t('foster-checkout', 'partialPayment.paymentCanceled');
					$cancelled->response = $intent->toArray();
					if (! $this->commerce()->getTransactions()->saveTransaction($cancelled)) {
						throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.canceledSaveFailed'));
					}

					self::$cancelledPayments[(int) $order->id] = true;
					continue;
				}
			}

			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.paymentInProgress'));
		}
	}

	private function supported(Gateway $gateway): bool
	{
		return $this->configured($gateway) && $gateway instanceof NetTerms && $gateway->paymentType === TransactionRecord::TYPE_PURCHASE;
	}

	private function save(Transaction $transaction, RequestResponseInterface $response): void
	{
		$transaction->status = $response->isSuccessful() ? TransactionRecord::STATUS_SUCCESS : TransactionRecord::STATUS_FAILED;
		$data = $response->getData();
		$transaction->response = collect(is_array($data) ? $data : [])->merge([
			self::MARKER => true,
		])->all();
		$transaction->code = $response->getCode();
		$transaction->reference = $response->getTransactionReference();
		$transaction->message = $response->getMessage();
		if (! $transaction->validate()) {
			throw new RuntimeException(implode(' ', $transaction->getFirstErrors()));
		}

		$this->persist($transaction);
	}

	private function persist(Transaction $transaction): void
	{
		// Commerce's saveTransaction completes an order after any payment, including a partial authorization.
		$record = $transaction->id !== null && $transaction->id !== 0 ? TransactionRecord::findOne($transaction->id) : new TransactionRecord();
		if (! $record instanceof TransactionRecord) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.transactionMissing'));
		}

		$record->setAttributes($transaction->getAttributes([
			'orderId', 'hash', 'gatewayId', 'type', 'status', 'amount', 'currency', 'paymentAmount', 'paymentCurrency',
			'paymentRate', 'reference', 'message', 'note', 'code', 'response', 'userId', 'parentId',
		]), false);
		if (! $record->save(false)) {
			throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.transactionSaveFailed'));
		}

		$transaction->id = $record->id;
		$transaction->getOrder()?->setTransactions(null);
		$this->commerce()->getTransactions()->trigger(Transactions::EVENT_AFTER_SAVE_TRANSACTION, new TransactionEvent([
			'transaction' => $transaction,
		]));
	}

	private function checkout(): Checkout
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->checkout;
	}

	private function commerce(): Commerce
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();

		return $commerce;
	}
}
