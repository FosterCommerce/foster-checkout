<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class DeliveryDateConfig extends Model
{
	/**
	 * Label for the delivery date.
	 */
	public ValueConfig $label;

	/**
	 * Message to display regarding delivery date.
	 */
	public ValueConfig $message;

	/**
	 * Value for the delivery date.
	 */
	public ValueConfig $estimate;

	/**
	 * Whether to display the delivery date.
	 */
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
