<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\elements\Order;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\helpers\UrlHelper;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\services\PartialPayments;
use fostercommerce\netterms\gateways\NetTerms;
use fostercommerce\netterms\models\payments\NetTermsPaymentForm;
use Throwable;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class PartialPaymentController extends BaseFrontEndController
{
	public function actionApply(): Response
	{
		return $this->change('apply');
	}

	public function actionRemove(): Response
	{
		return $this->change('remove');
	}

	public function actionComplete(): Response
	{
		return $this->change('complete');
	}

	private function change(string $operation): Response
	{
		$this->requirePostRequest();
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$cart = $commerce->getCarts()->getCart();
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$checkout = $plugin->getCheckout();
		if (! $cart->id) {
			return $this->asFailure(Craft::t('foster-checkout', 'partialPayment.cartUnavailable')) ?? $this->redirectToPostedUrl();
		}

		$lockName = 'order:' . $cart->number;
		$mutex = Craft::$app->getMutex();
		if (! $mutex->acquire($lockName, 5)) {
			return $this->asFailure(Craft::t('foster-checkout', 'partialPayment.cartBusy')) ?? $this->redirectToPostedUrl();
		}

		$error = '';
		try {
			$order = Order::find()->id($cart->id)->one();
			if (! $order instanceof Order) {
				throw new UserException(Craft::t('foster-checkout', 'partialPayment.cartUnavailable'));
			}

			// Skip the operation for a post that waited on the lock while the order completed
			if (! $order->isCompleted) {
				$this->runOperation($operation, $order, $plugin->getPartialPayments());
			}
		} catch (Throwable $throwable) {
			$error = $this->customerMessage($throwable);
			if (! $this->request->getAcceptsJson()) {
				Craft::$app->getSession()->setError($error);
			}

			$order = Order::find()->id($cart->id)->one();
		} finally {
			$mutex->release($lockName);
		}

		$path = $checkout->settings()->paths->checkout;
		$redirect = $order instanceof Order && $order->isCompleted
			? UrlHelper::siteUrl($path . '/order', [
				'number' => $order->number,
				'success' => 'true',
			])
			: null;
		if ($this->request->getAcceptsJson()) {
			$cartInfo = $order instanceof Order ? $this->cartArray($order) : [];
			if ($order instanceof Order) {
				$cartInfo['fosterCheckout'] = $checkout->checkoutLiveState($order);
			}

			return $this->asJson([
				'success' => $error === '',
				'error' => $error,
				'cart' => $cartInfo,
				'redirect' => $redirect,
			]);
		}

		return $redirect !== null ? $this->redirect($redirect) : $this->redirectToPostedUrl();
	}

	/**
	 * @throws UserException
	 * @throws BadRequestHttpException
	 */
	private function runOperation(string $operation, Order $order, PartialPayments $partialPayments): void
	{
		if ($operation === 'complete') {
			$partialPayments->complete($order);
			return;
		}

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$gatewayValue = $this->request->getRequiredBodyParam('gatewayId');
		$gatewayId = is_scalar($gatewayValue) ? (int) $gatewayValue : 0;
		$gateway = $commerce->getGateways()->getGatewayById($gatewayId);
		if (! $gateway instanceof NetTerms) {
			throw new UserException(Craft::t('foster-checkout', 'partialPayment.methodUnavailable'));
		}

		if ($operation === 'remove') {
			$transactionValue = $this->request->getRequiredBodyParam('transactionId');
			$transactionId = is_numeric($transactionValue) ? (int) $transactionValue : 0;
			$transaction = collect($order->getTransactions())
				->first(static fn (Transaction $transaction): bool => PartialPayments::isPartialTransaction($transaction)
					&& in_array($transaction->type, [TransactionRecord::TYPE_PURCHASE, TransactionRecord::TYPE_AUTHORIZE], true)
					&& $transaction->status === TransactionRecord::STATUS_SUCCESS
					&& (int) $transaction->gatewayId === $gatewayId
					&& $transaction->id === $transactionId);
			if ($transaction instanceof Transaction) {
				$gateway->withPartialRefundLock($transaction, static function () use ($partialPayments, $order, $gatewayId, $transactionId): void {
					$partialPayments->remove($order, $gatewayId, $transactionId);
				});
			}

			return;
		}

		$expectedValue = $this->request->getBodyParam('expectedAmount');
		$expectedAmount = is_numeric($expectedValue) ? (float) $expectedValue : null;
		/** @var NetTermsPaymentForm $form */
		$form = $gateway->getPaymentFormModel();
		$form->accountId = $this->request->getBodyParam('paymentForm.accountId');
		$gateway->withPartialPaymentLock($order, $form, static function () use ($partialPayments, $order, $gateway, $form, $expectedAmount): void {
			$partialPayments->apply($order, $gateway, $form, $expectedAmount);
		});
	}

	private function customerMessage(Throwable $throwable): string
	{
		if ($throwable instanceof UserException) {
			return in_array($throwable->getMessage(), ['', '0'], true) ? Craft::t('foster-checkout', 'partialPayment.failed') : $throwable->getMessage();
		}

		// Log the exception rather than show it, since a database or API error can name internal details
		Craft::$app->getErrorHandler()->logException($throwable);

		return Craft::t('foster-checkout', 'partialPayment.failed');
	}
}
