<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class DeliveryDateConfig extends Model
{
	public ValueConfig $label;

	public ValueConfig $message;

	public ValueConfig $estimate;

	public ValueConfig $display;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		if (isset($config['label'])) {
			$config['label'] = ValueConfig::fromConfig('label', $config);
		} else {
			$config['label'] = new ValueConfig();
		}

		if (isset($config['message'])) {
			$config['message'] = ValueConfig::fromConfig('message', $config);
		} else {
			$config['message'] = new ValueConfig();
		}

		if (isset($config['estimate'])) {
			$config['estimate'] = ValueConfig::fromConfig('estimate', $config);
		} else {
			$config['estimate'] = new ValueConfig();
		}

		if (isset($config['display'])) {
			$config['display'] = ValueConfig::fromConfig('display', $config);
		} else {
			$config['display'] = new ValueConfig();
		}

		parent::__construct($config);
	}
}
