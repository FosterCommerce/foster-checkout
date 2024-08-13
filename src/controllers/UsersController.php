<?php

namespace fostercommerce\craftfostercheckout\controllers;

use craft\web\Controller;
use fostercommerce\craftfostercheckout\FosterCheckout;
use yii\web\Response;

/**
 * Users controller
 */
class UsersController extends Controller
{
    protected array|int|bool $allowAnonymous = ['get-user-addresses', 'get-user-exists'];

    /**
     * foster-checkout/users action
     */
    public function actionGetUserAddresses(): Response
    {
        return $this->asJson([
           'addresses' => FosterCheckout::getInstance()->users->getUserAddresses()
        ]);
    }

    public function actionGetUserExists($email): Response
    {
        return $this->asJson([
            'userExists' => FosterCheckout::getInstance()->users->getUserExists($email)
        ]);
    }
}
