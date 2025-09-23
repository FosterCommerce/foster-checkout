<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class PaymentGatewayFieldConfig extends Model
{
	/**
	 * The brand primary custom color in HEX color
	 */
	public string $handle = '';

	/**
	 * The Google web font (https://fonts.google.com/) family name you want to use
	 * (ex. 'Roboto Slab')
	 */
	public string $type = 'text';

	/**
	 * The first part of the text in the title meta tag.
	 * Leave blank to use the Craft's siteName
	 */
	public string $label = '';

	public int|bool $maxLength = false;

	public int|bool $min = false;

	public int|bool $max = false;

	public bool $required = false;

	public string $placeholder = '';

	public function __construct($handle, $config = [])
	{
		parent::__construct($config);
		$this->handle = $handle;
//		if (array_key_exists('type', $config)) {
//			$this->type = $config['type'];
//		}
	}
}
