<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class PaymentGatewayConfig extends Model
{
	/**
	 * The fields to be rendered for this specific gateway
	 *
	 * @var array<PaymentGatewayFieldConfig>
	 */
	public array $fields = [];

	/**
	 * @param string $handle;
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(public string $handle, $config = [])
	{
		parent::__construct($config);
		if (array_key_exists('fields', $config)) {
			$this->fields = [];
			foreach ($config['fields'] as $fieldHandle => $field) {
				$this->fields[] = new PaymentGatewayFieldConfig($fieldHandle, $field);
			}
		}
	}
}
