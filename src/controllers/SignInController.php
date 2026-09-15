<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\User as WebUser;
use fostercommerce\fostercheckout\FosterCheckout;
use yii\web\Response;

class SignInController extends Controller
{
	protected array|bool|int $allowAnonymous = true;

	/**
	 * Note where to return to, then hand the customer to the site's login page.
	 *
	 * The return URL is read from the plugin's settings rather than the request, so a crafted link
	 * cannot choose where someone lands once they have signed in.
	 */
	public function actionIndex(): Response
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		/** @var WebUser $userSession */
		$userSession = Craft::$app->getUser();

		// The checkout index picks the step, which a remembered one would get wrong for a customer
		// who signs in and turns out to have addresses on file already
		$userSession->setReturnUrl(UrlHelper::siteUrl($plugin->getCheckout()->settings()->paths->checkout));

		/** @var string $loginPath */
		$loginPath = Craft::$app->getConfig()->getGeneral()->getLoginPath();

		return $this->redirect(UrlHelper::siteUrl($loginPath));
	}
}
