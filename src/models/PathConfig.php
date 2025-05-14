<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class PathConfig extends Model
{
	/**
	 * The site relative path to where the cart should be accessible
	 */
	public string $cart = 'cart';

	/**
	 * If true, use the cart template for the cart page, otherwise use the path only and let users define their own cart template.
	 */
	public bool $useCartTemplate = true;

	/**
	 * The site relative path to where the checkout should be accessible
	 */
	public string $checkout = 'checkout';

	/**
	 * The path the user should be taken to if they cancel the checkout process
	 */
	public string $cancel = '/';

	/**
	 * The path the user should be taken to if they click the logo
	 * (ex. '/')
	 */
	public string $logo = '/';

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

		// Always ensure there's no leading or trailing slashes
		$this->cart = trim($this->cart, '/');
		$this->checkout = trim($this->checkout, '/');
	}
}
