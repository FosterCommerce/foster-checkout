<?php

namespace fostercommerce\fostercheckout\helpers;

use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Formatter\FormatterInterface;
use craft\elements\Address;

class CheckoutAddressFormatter implements FormatterInterface
{
	/**
	 * @param array<mixed, mixed> $options
	 */
	#[\Override]
	public function format(AddressInterface $address, array $options = []): string
	{
		/** @var Address $address */
		$addressLines = [
			$address->addressLine1,
			$address->addressLine2,
			$address->addressLine3,
			$address->locality,
			$address->dependentLocality,
			$address->administrativeArea,
			$address->postalCode,
			$address->getCountry()->getName(),
		];

		return implode(', ', array_filter($addressLines));
	}
}
