<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class PaymentGatewayFieldConfig extends Model
{
	/**
	 * @var string the type of input this is, currently supports text and number
	 */
	public string $type = 'text';

	/**
	 * @var string the label to display above the input field
	 */
	public string $label = '';

	/**
	 * @var string|null the name attribute for the input field
	 */
	public string|null $name = null;

	/**
	 * @var string|null the id attribute for the input field
	 */
	public string|null $id = null;

	/** @var int|bool max length for input if type text */
	public int|bool $maxLength = false;

	/** @var int|bool min value if type number */
	public int|bool $min = false;

	/** @var int|bool max value if type number */
	public int|bool $max = false;

	/**
	 * @var bool is the field value a required field
	 */
	public bool $required = false;

	/**
	 * @var string placeholder text for input
	 */
	public string $placeholder = '';

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(
		public string $handle,
		$config = []
	) {
		parent::__construct($config);
		//		if (array_key_exists('type', $config)) {
		//			$this->type = $config['type'];
		//		}
	}
}
