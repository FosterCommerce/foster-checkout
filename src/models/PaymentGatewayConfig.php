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

	public ValueConfig $note;

	/**
	 * @var int|null number of columns in this layout
	 */
	public null|int $columns = 3;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(
		public string $handle,
		$config = []
	) {
		parent::__construct($config);
		if (array_key_exists('fields', $config)) {
			$this->fields = [];
			foreach ($config['fields'] as $fieldHandle => $field) {
				$this->fields[] = new PaymentGatewayFieldConfig($fieldHandle, $field);
			}
		}

		if (! isset($this->note)) {
			$this->note = new ValueConfig();
		}
	}
}
