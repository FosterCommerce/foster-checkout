<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class IncludesConfig extends Model
{
	/**
	 * The host site relative template path to the head include
	 */
	public string $head = '';

	/**
	 * The host site relative template path to the body include
	 */
	public string $body = '';

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		// Always ensure there's no leading or trailing slashes
		$this->head = trim($this->head, '/');
		$this->body = trim($this->body, '/');
	}
}
