<?php

namespace fostercommerce\craftfostercheckout\services;

use Craft;
use craft\elements\Address;
use craft\elements\User;
use yii\base\Component;

/**
 * Users service
 *
 * @property-read array $userAddresses
 */
class Users extends Component
{
    public function getUserAddresses(): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        $addresses = [];
        if ($user) {
            $addresses = Address::find()->owner($user)->all();
        }
        return $addresses;
    }

    public function getUserExists($email): bool
    {
        $userQuery = User::find()->email($email)->status('active')->one();

        return $userQuery ? true : false;
    }
}
