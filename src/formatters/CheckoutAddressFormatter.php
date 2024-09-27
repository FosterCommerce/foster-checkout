<?php

namespace fostercommerce\fostercheckout\formatters;

use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use Craft;
use craft\elements\Address;

class CheckoutAddressFormatter extends DefaultFormatter
{
	/**
	 * @param array<mixed, mixed> $options
	 */
	public function format(AddressInterface $address, array $options = []): string
	{
		/** @var Address $address */
		if (property_exists($address, 'firstName')) {
			$address->firstName = null;
		}

		if (property_exists($address, 'lastName')) {
			$address->lastName = null;
		}

		// if the address is in the US then get the State name, otherwise use the inputted value
		//$state = $address->countryCode == 'US' ? Craft::$app->getAddresses()->subdivisionRepository->get($address->administrativeArea, [$address->countryCode])->getName() : $address->administrativeArea;

		$addressLines[] = $address->addressLine1;
		$addressLines[] = $address->addressLine2;
		$addressLines[] = $address->addressLine3;
		$addressLines[] = $address->locality;
		$addressLines[] = $address->dependentLocality;
		$addressLines[] = $address->administrativeArea;
		$addressLines[] = $address->postalCode;
		$addressLines[] = $address->getCountry()->getName();

		return implode(', ', array_filter($addressLines));
	}
}
