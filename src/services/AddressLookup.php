<?php

namespace fostercommerce\fostercheckout\services;

use craft\helpers\App;
use fostercommerce\fostercheckout\addresslookups\Google;
use fostercommerce\fostercheckout\addresslookups\Loqate;
use fostercommerce\fostercheckout\base\AddressLookupInterface;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\AddressLookupConfig;
use yii\base\Component;

class AddressLookup extends Component
{
	public function provider(): ?AddressLookupInterface
	{
		$config = FosterCheckout::getInstance()?->getCheckout()->settings()->addressLookup;

		if (! $config instanceof AddressLookupConfig || ! $config->enabled) {
			return null;
		}

		$apiKey = trim((string) App::parseEnv($config->apiKey));

		if ($apiKey === '') {
			return null;
		}

		return match ($config->provider) {
			AddressLookupConfig::PROVIDER_LOQATE => new Loqate($apiKey),
			default => new Google($apiKey),
		};
	}
}
