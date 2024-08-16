<?php

namespace fostercommerce\craftfostercheckout\controllers;

use craft\web\Controller;
use fostercommerce\craftfostercheckout\FosterCheckout;
use yii\web\Response;
use yii\web\Request;

/**
 * Checkout controller
 */
class CheckoutController extends Controller
{
    protected array|int|bool $allowAnonymous = ['get-critical-data', 'get-payment-form', 'get-discounts', 'check-email'];

    public function actionGetCriticalData(): Response
    {
        return $this->asJson([
            'countries' => FosterCheckout::getInstance()->checkout->getCountries(),
            'regions' => FosterCheckout::getInstance()->checkout->getRegions(),
            'paths' => FosterCheckout::getInstance()->getSettings()->paths,
            'gateways' => FosterCheckout::getInstance()->checkout->getGateways(),
        ]);
    }

    public function actionGetPaymentForm(): Response
    {
        return $this->asJson([
            'paymentForm' => FosterCheckout::getInstance()->checkout->getPaymentForm(),
        ]);
    }

    public function actionGetDiscounts(): Response
    {
        return $this->asJson([
            'discounts' => FosterCheckout::getInstance()->checkout->getDiscounts(),
        ]);
    }

}
