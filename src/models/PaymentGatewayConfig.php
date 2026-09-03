<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Model;

class PaymentGatewayConfig extends Model
{
	/**
	 * Settings a config file may still name. Gateway fields moved to a field layout per gateway.
	 *
	 * @var list<string>
	 */
	private const array REMOVED_SETTINGS = ['fields'];

	public ValueConfig $note;

	/**
	 * Extra params merged into the gateway's payment form params (e.g. PayPal SDK options like `disable-funding`)
	 *
	 * @var array<string, mixed>
	 */
	public array $params = [];

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(
		public string $handle,
		$config = []
	) {
		parent::__construct($config);

		if (! isset($this->note)) {
			$this->note = new ValueConfig();
		}
	}

	#[\Override]
	public function __set($name, $value): void
	{
		if (in_array($name, self::REMOVED_SETTINGS, true)) {
			// The deprecator throws whenever a site sets throwExceptions, which craft-config ties to devMode
			Craft::warning("`{$name}` has been replaced by the gateway's field layout.", 'deprecation-error');

			return;
		}

		parent::__set($name, $value);
	}
}
