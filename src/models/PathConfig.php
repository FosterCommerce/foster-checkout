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
}
