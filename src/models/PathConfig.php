<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class PathConfig extends Model
{
	public string $cart = 'cart';

	public bool $useCartTemplate = true;

	public string $checkout = 'checkout';

	public string $cancel = '/';

	/**
	 * The site relative path to where the account should be accessible
	 * (ex. '/')
	 */
	public string $account = '/';

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->cart = trim($this->cart, '/');
		$this->checkout = trim($this->checkout, '/');
	}
}
