<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\web\Controller;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\services\PaymentRequests;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * @since 2.1.0
 */
class PaymentRequestsController extends Controller
{
	public function actionSend(): ?Response
	{
		$this->requirePostRequest();
		$this->requireCpRequest();
		$this->requirePermission(PaymentRequests::PERMISSION);

		/** @var Order|null $order */
		$order = Order::find()->id($this->request->getRequiredBodyParam('orderId'))->isCompleted(true)->one();
		if (! $order instanceof Order) {
			throw new NotFoundHttpException('Order not found');
		}

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$paymentRequests = $plugin->getPaymentRequests();

		if (! $paymentRequests->canRequest($order)) {
			return $this->asFailure(Craft::t(FosterCheckout::HANDLE, 'paymentRequest.nothingDue'));
		}

		if (! $paymentRequests->send($order)) {
			return $this->asFailure(Craft::t(FosterCheckout::HANDLE, 'paymentRequest.notSent'));
		}

		return $this->asSuccess(Craft::t(FosterCheckout::HANDLE, 'paymentRequest.sent', [
			'amount' => $paymentRequests->balance($order),
		]));
	}
}
