<?php

namespace fostercommerce\fostercheckout\controllers;

use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use fostercommerce\fostercheckout\FosterCheckout;
use yii\validators\EmailValidator;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Subscribes the customer who ticked the checkout's newsletter checkbox.
 *
 * @since 2.0.0
 */
class NewsletterController extends BaseFrontEndController
{
	public function actionSubscribe(): Response
	{
		$this->requirePostRequest();
		$this->requireAcceptsJson();

		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$cart = $commerce->getCarts()->getCart();

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$checkout = $plugin->getCheckout();

		if (! $checkout->offersNewsletter($cart)) {
			throw new ForbiddenHttpException('The newsletter checkbox is not offered for this cart.');
		}

		// Prefer the posted email, since the stepped email step saves it to the cart in a parallel request
		$email = $this->request->getBodyParam('email') ?: $cart->email;

		// Match Craft's user email rule, which the checkout's own email check mirrors
		$emailValidator = new EmailValidator([
			'enableIDN' => App::supportsIdn(),
			'enableLocalIDN' => App::supportsIdn(),
		]);

		if (! is_string($email) || ! $emailValidator->validate($email)) {
			throw new BadRequestHttpException('A valid email is required to subscribe.');
		}

		$checkout->subscribeToNewsletter($cart, $email);

		return $this->asJson([
			'success' => true,
		]);
	}
}
