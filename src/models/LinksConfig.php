<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class LinksConfig extends Model
{
	public ValueConfig $footerLinks;


	/**
	 * @param array<mixed, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (! isset($this->footerLinks)) {
			$this->footerLinks = new ValueConfig();
		}
	}
}
