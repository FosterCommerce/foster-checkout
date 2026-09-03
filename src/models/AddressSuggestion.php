<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class AddressSuggestion extends Model
{
	public string $id = '';

	public string $label = '';

	/**
	 * Loqate returns streets and postal districts alongside addresses. Only an address can be
	 * retrieved, so the rest are searched again as a container.
	 */
	public bool $isFinal = true;
}
