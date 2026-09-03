<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class AddressLookupConfig extends Model
{
	public const string PROVIDER_GOOGLE = 'google';

	public const string PROVIDER_LOQATE = 'loqate';

	public bool $enabled = false;

	public string $provider = self::PROVIDER_GOOGLE;

	/**
	 * An env var name such as `$FC_GOOGLE_PLACES_KEY`, so the key stays out of project config.
	 */
	public ?string $apiKey = null;
}
