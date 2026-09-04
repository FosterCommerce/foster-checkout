<?php

namespace fostercommerce\fostercheckout\addresslookups;

use Craft;
use craft\helpers\Json;
use fostercommerce\fostercheckout\base\AddressLookupInterface;
use fostercommerce\fostercheckout\models\AddressSuggestion;

class Google implements AddressLookupInterface
{
	private const string AUTOCOMPLETE_URL = 'https://places.googleapis.com/v1/places:autocomplete';

	private const string DETAILS_URL = 'https://places.googleapis.com/v1/places/';

	private const int TIMEOUT = 5;

	public function __construct(
		private readonly string $apiKey,
	) {
	}

	#[\Override]
	public function suggest(string $query, string $countryCode, ?string $container, string $session): array
	{
		$body = [
			'input' => $query,
			'sessionToken' => $session,
		];

		if ($countryCode !== '') {
			$body['includedRegionCodes'] = [$countryCode];
		}

		$response = $this->request('POST', self::AUTOCOMPLETE_URL, [
			'headers' => [
				'X-Goog-Api-Key' => $this->apiKey,
			],
			'json' => $body,
		]);

		$suggestions = [];

		$items = is_array($response['suggestions'] ?? null) ? $response['suggestions'] : [];

		foreach ($items as $item) {
			$prediction = is_array($item) && is_array($item['placePrediction'] ?? null)
				? $item['placePrediction']
				: null;

			if ($prediction === null) {
				continue;
			}

			$text = is_array($prediction['text'] ?? null) ? $prediction['text'] : [];

			$suggestions[] = new AddressSuggestion([
				'id' => $this->text($prediction['placeId'] ?? null),
				'label' => $this->text($text['text'] ?? null),
			]);
		}

		return $suggestions;
	}

	#[\Override]
	public function retrieve(string $id, string $session): array
	{
		$response = $this->request('GET', self::DETAILS_URL . rawurlencode($id), [
			'headers' => [
				'X-Goog-Api-Key' => $this->apiKey,
				'X-Goog-FieldMask' => 'addressComponents',
			],
			'query' => [
				'sessionToken' => $session,
			],
		]);

		$returned = is_array($response['addressComponents'] ?? null) ? $response['addressComponents'] : [];

		if ($returned === []) {
			return [];
		}

		$components = [];

		foreach ($returned as $component) {
			if (! is_array($component)) {
				continue;
			}

			$types = is_array($component['types'] ?? null) ? $component['types'] : [];

			foreach ($types as $type) {
				$components[$this->text($type)] = [
					'long' => $this->text($component['longText'] ?? null),
					'short' => $this->text($component['shortText'] ?? null),
				];
			}
		}

		$streetNumber = $components['street_number']['long'] ?? '';
		$route = $components['route']['long'] ?? '';

		return [
			'addressLine1' => trim($streetNumber . ' ' . $route),
			'addressLine2' => $components['subpremise']['long'] ?? '',
			'locality' => $components['locality']['long'] ?? '',
			// Craft stores the subdivision code, not the full name
			'administrativeArea' => $components['administrative_area_level_1']['short'] ?? '',
			'postalCode' => $components['postal_code']['long'] ?? '',
			'countryCode' => $components['country']['short'] ?? '',
		];
	}

	private function text(mixed $value): string
	{
		return is_string($value) ? $value : '';
	}

	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	private function request(string $method, string $url, array $options): array
	{
		$response = Craft::createGuzzleClient([
			'timeout' => self::TIMEOUT,
		])->request($method, $url, $options);

		$decoded = Json::decode((string) $response->getBody());

		return is_array($decoded) ? $decoded : [];
	}
}
