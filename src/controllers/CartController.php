<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\Plugin as Commerce;
use yii\web\Response;

/**
 * Moves a cleared cart to the trash, for stores that don't want cleared carts kept as inactive carts.
 *
 * @since 2.1.0
 */
class CartController extends BaseFrontEndController
{
	public function actionDelete(): ?Response
	{
		$this->requirePostRequest();

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$carts = $commerce->getCarts();
		$cart = $carts->getCart();

		// Skip an unsaved cart, which has no row to delete
		if ($cart->id !== null) {
			Craft::$app->getElements()->deleteElement($cart);
		}

		$carts->forgetCart();

		return $this->redirectToPostedUrl();
	}
}
