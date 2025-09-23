<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;
use fostercommerce\fostercheckout\models\PaymentGatewayFieldConfig;

class PaymentGatewayConfig extends Model
{
	public string $handle;

	/**
	 * The fields to be rendered for this specific gatewy
	 *
	 * @var array<PaymentGatewayFieldConfig>
	 */

	public array $fields = [];

	public function __construct($handle, $config = [])
	{
		parent::__construct($config);
		$this->handle = $handle;
		if (array_key_exists('fields', $config)) {
			$this->fields = [];
			foreach ($config['fields'] as $fieldHandle => $field) {
				$this->fields[] = new PaymentGatewayFieldConfig($fieldHandle, $field);
			}
		}
	}
}
