<?php

namespace fostercommerce\fostercheckout\base;

use fostercommerce\fostercheckout\models\AddressSuggestion;

interface AddressLookupInterface
{
	/**
	 * Each provider ignores one argument: Google has no container, Loqate has no session.
	 *
	 * @return list<AddressSuggestion>
	 */
	public function suggest(string $query, string $countryCode, ?string $container, string $session): array;

	/**
	 * @return array<string, string> address attributes keyed as a Craft address element
	 */
	public function retrieve(string $id, string $session): array;
}
