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
use RuntimeException;
use Throwable;
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
		$checkout = $plugin->checkout;
		$lockName = 'order:' . $cart->number;
		$mutex = Craft::$app->getMutex();
		if (! $cart->id || ! $mutex->acquire($lockName, 5)) {
			if (! $this->request->getAcceptsJson()) {
				Craft::$app->getSession()->setError(Craft::t('foster-checkout', 'partialPayment.cartBusy'));
				return $this->redirectToPostedUrl();
			}

			throw new BadRequestHttpException(Craft::t('foster-checkout', 'partialPayment.cartBusy'));
		}

		$error = '';
		try {
			$order = Order::find()->id($cart->id)->one();
			try {
				if (! $order instanceof Order || $order->isCompleted) {
					throw new BadRequestHttpException(Craft::t('foster-checkout', 'partialPayment.cartUnavailable'));
				}

				$partialPayments = $plugin->partialPayments;
				if ($operation === 'complete') {
					$partialPayments->complete($order);
				} else {
					$gatewayValue = $this->request->getRequiredBodyParam('gatewayId');
					$gatewayId = is_scalar($gatewayValue) ? (int) $gatewayValue : 0;
					$gateway = $commerce->getGateways()->getGatewayById($gatewayId);
					if (! $gateway instanceof NetTerms) {
						throw new RuntimeException(Craft::t('foster-checkout', 'partialPayment.methodUnavailable'));
					}

					$attributes = $this->request->getBodyParam('paymentForm', []);
					$attributes = is_array($attributes) ? $attributes : [];
					$expectedValue = $this->request->getBodyParam('expectedAmount');
					$expectedAmount = is_numeric($expectedValue) ? (float) $expectedValue : null;
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
					} else {
						$form = $gateway->getPaymentFormModel();
						$form->setAttributes([
							'accountId' => $attributes['accountId'] ?? null,
						], false);
						$gateway->withPartialPaymentLock($order, $form, static function () use ($partialPayments, $order, $gatewayId, $attributes, $expectedAmount): void {
							$partialPayments->apply($order, $gatewayId, $attributes, $expectedAmount);
						});
					}
				}
			} catch (Throwable $exception) {
				$error = $exception->getMessage();
				if (! $this->request->getAcceptsJson()) {
					Craft::$app->getSession()->setError($error);
				}

				$order = Order::find()->id($cart->id)->one();
			}
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
}
