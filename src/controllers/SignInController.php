<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\elements\Order;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\User as WebUser;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\services\PaymentRequests;
use yii\web\Response;

class SignInController extends Controller
{
	protected array|bool|int $allowAnonymous = true;

	/**
	 * Note where to return to, then hand the customer to the site's login page.
	 *
	 * The return URL is the checkout or a valid pay link's page, so a crafted link can't pick another.
	 */
	public function actionIndex(): Response
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		/** @var WebUser $userSession */
		$userSession = Craft::$app->getUser();

		$paymentRequests = $plugin->getPaymentRequests();
		$token = $this->request->getQueryParam(PaymentRequests::TOKEN_PARAM);

		// Return to the checkout index rather than a step, since a customer who signs in can have addresses on file
		$userSession->setReturnUrl(is_string($token) && $paymentRequests->orderForToken($token) instanceof Order
			? $paymentRequests->payPageUrl($token)
			: UrlHelper::siteUrl($plugin->getCheckout()->settings()->paths->checkout));

		/** @var string $loginPath */
		$loginPath = Craft::$app->getConfig()->getGeneral()->getLoginPath();

		return $this->redirect(UrlHelper::siteUrl($loginPath));
	}
}
