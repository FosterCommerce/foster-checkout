<?php

namespace fostercommerce\craftfostercheckout\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use fostercommerce\craftfostercheckout\FosterCheckout;
use yii\web\Response;

/**
 * Checkout controller
 */
class CheckoutController extends Controller
{

    protected array|int|bool $allowAnonymous = ['get-critical-data', 'get-discounts'];

    public function actionGetCriticalData(): Response
    {
        return $this->asJson([
            'countries' => FosterCheckout::getInstance()->checkout->getCountries(),
            'regions' => FosterCheckout::getInstance()->checkout->getRegions(),
            'paths' => FosterCheckout::getInstance()->getSettings()->paths,
            'productFields' => FosterCheckout::getInstance()->checkout->productFields()
        ]);
    }

    public function actionGetDiscounts(): Response
    {
        return $this->asJson([
            'discounts' => FosterCheckout::getInstance()->checkout->getDiscounts(),
        ]);
    }

    public function actionGetVariants(): Response
    {

        $ids = $this->request->getParam('ids');

        return $this->asJson(
            FosterCheckout::getInstance()->checkout->getVariants($ids)
        );

    }

}
