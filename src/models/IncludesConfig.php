<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class IncludesConfig extends Model
{
	public string $head = '';

	public string $body = '';

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->head = trim($this->head, '/');
		$this->body = trim($this->body, '/');
	}
}
