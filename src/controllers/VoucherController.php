<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\Plugin as Commerce;
use verbb\giftvoucher\GiftVoucher;
use verbb\giftvoucher\storage\Order as OrderCodeStorage;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Applies a gift voucher code to the cart.
 *
 * Gift Voucher's own `cart/add-code` falls back to Commerce coupon codes, so a discount code typed
 * into the voucher form replaces the order's coupon and still reports the voucher as failed.
 */
class VoucherController extends BaseFrontEndController
{
	public function actionAddCode(): ?Response
	{
		$this->requirePostRequest();

		if (! Craft::$app->getPlugins()->isPluginEnabled('gift-voucher')) {
			throw new NotFoundHttpException();
		}

		/** @var string $voucherCode */
		$voucherCode = $this->request->getRequiredBodyParam('voucherCode');
		$voucherCode = trim($voucherCode);

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$cart = $commerce->getCarts()->getCart();
		$cartVariable = $commerce->getSettings()->cartVariable;

		/** @var GiftVoucher $giftVoucher */
		$giftVoucher = GiftVoucher::$plugin;

		$codeStorage = $giftVoucher->getCodeStorage();
		$error = '';

		// Save the cart so the response has the voucher adjustment, unless order code storage already saved it
		$applied = $giftVoucher->getCodes()->matchCode($voucherCode, $error)
			&& $codeStorage->add($voucherCode, $cart)
			&& ($codeStorage instanceof OrderCodeStorage || Craft::$app->getElements()->saveElement($cart, false, false, $commerce->getSettings()->updateCartSearchIndexes));

		// Set a message for a failed add() or cart save, since neither sets one
		if (! $applied && $error === '') {
			$error = Craft::t('foster-checkout', 'voucher.applyFailed');
		}

		if ($this->request->getAcceptsJson()) {
			return $this->asJson([
				'success' => $applied,
				'error' => $error,
				$cartVariable => $this->cartArray($cart),
			]);
		}

		if ($applied) {
			return $this->redirectToPostedUrl();
		}

		// The stepped checkout reads the message off the cart it renders
		$cart->addError('voucherCode', $error);
		Craft::$app->getUrlManager()->setRouteParams([
			$cartVariable => $cart,
		]);

		return null;
	}
}
