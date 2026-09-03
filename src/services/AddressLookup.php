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
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $this->providerFor($plugin->getCheckout()->settings()->addressLookup);
	}

	public function providerFor(AddressLookupConfig $config): ?AddressLookupInterface
	{
		if (! $config->enabled) {
			return null;
		}

		$apiKey = trim((string) App::parseEnv($config->apiKey));

		if ($apiKey === '') {
			return null;
		}

		return match ($config->provider) {
			AddressLookupConfig::PROVIDER_LOQATE => new Loqate($apiKey),
			AddressLookupConfig::PROVIDER_GOOGLE => new Google($apiKey),
			default => null,
		};
	}
}
