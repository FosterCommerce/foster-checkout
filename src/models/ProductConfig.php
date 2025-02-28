<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class ProductConfig extends Model
{
	public ?string $productImageHandle = null;

	public ?string $variantImageHandle = null;
}
