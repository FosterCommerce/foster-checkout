<?php

namespace fostercommerce\fostercheckout\addresslookups;

use Craft;
use craft\helpers\Json;
use fostercommerce\fostercheckout\base\AddressLookupInterface;
use fostercommerce\fostercheckout\models\AddressSuggestion;
use RuntimeException;

class Loqate implements AddressLookupInterface
{
	private const string FIND_URL = 'https://api.addressy.com/Capture/Interactive/Find/v1.1/json3.ws';

	private const string RETRIEVE_URL = 'https://api.addressy.com/Capture/Interactive/Retrieve/v1.2/json3.ws';

	private const int TIMEOUT = 5;

	public function __construct(
		private readonly string $apiKey,
	) {
	}

	#[\Override]
	public function suggest(string $query, string $countryCode, ?string $container, string $session): array
	{
		$items = $this->request(self::FIND_URL, [
			'Key' => $this->apiKey,
			'Text' => $query,
			'Countries' => $countryCode,
			'Container' => $container ?? '',
		]);

		$suggestions = [];

		foreach ($items as $item) {
			$suggestions[] = new AddressSuggestion([
				'id' => $this->text($item['Id'] ?? null),
				'label' => trim($this->text($item['Text'] ?? null) . ' ' . $this->text($item['Description'] ?? null)),
				'isFinal' => ($item['Type'] ?? null) === 'Address',
			]);
		}

		return $suggestions;
	}

	#[\Override]
	public function retrieve(string $id, string $session): array
	{
		$items = $this->request(self::RETRIEVE_URL, [
			'Key' => $this->apiKey,
			'Id' => $id,
		]);

		$address = $items[0] ?? [];

		if ($address === []) {
			return [];
		}

		return [
			'addressLine1' => $this->text($address['Line1'] ?? null),
			'addressLine2' => $this->text($address['Line2'] ?? null),
			'locality' => $this->text($address['City'] ?? null),
			'administrativeArea' => $this->text($address['ProvinceCode'] ?? null),
			'postalCode' => $this->text($address['PostalCode'] ?? null),
			'countryCode' => $this->text($address['CountryIso2'] ?? null),
		];
	}

	private function text(mixed $value): string
	{
		return is_string($value) ? $value : '';
	}

	/**
	 * @param array<string, string> $query
	 * @return list<array<string, mixed>>
	 */
	private function request(string $url, array $query): array
	{
		$response = Craft::createGuzzleClient([
			'timeout' => self::TIMEOUT,
		])->get($url, [
			'query' => $query,
		]);

		$decoded = Json::decode((string) $response->getBody());
		$items = is_array($decoded) && is_array($decoded['Items'] ?? null) ? $decoded['Items'] : [];

		// Loqate reports a bad key or a malformed request as a 200 carrying one Error item
		if (isset($items[0]['Error'])) {
			throw new RuntimeException($this->text($items[0]['Description'] ?? null));
		}

		return array_values(array_filter($items, 'is_array'));
	}
}
