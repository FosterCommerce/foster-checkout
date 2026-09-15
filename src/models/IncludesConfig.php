<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class IncludesConfig extends Model
{
	public string $head = '';

	public string $body = '';

	/**
	 * Rendered in its own container above the checkout summary, where a store shows its own message
	 * about the order, such as how much more it takes to earn free shipping.
	 */
	public string $summary = '';

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->head = trim($this->head, '/');
		$this->body = trim($this->body, '/');
		$this->summary = trim($this->summary, '/');
	}
}
