<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use craft\helpers\Json;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\caching\CacheInterface;

/**
 * @phpstan-type MolliePaymentMethod array{id: string, name: string, image: string, issuers: list<array{id: string, name: string}>}
 */
class MolliePaymentMethods extends Component
{
	public const string GATEWAY_CLASS = 'craft\\commerce\\mollie\\gateways\\Gateway';

	private const string METHODS_URL = 'https://api.mollie.com/v2/methods';

	private const int TIMEOUT = 5;

	private const int CACHE_DURATION = 300;

	private const int FAILURE_CACHE_DURATION = 60;

	// From Mollie's list methods reference, with each language's default region first
	private const array LOCALES = [
		'en_US', 'en_GB', 'en_BE', 'en_NL', 'de_DE', 'de_AT', 'de_CH', 'de_LU', 'fr_FR', 'fr_BE', 'fr_LU',
		'nl_NL', 'nl_BE', 'ca_ES', 'cs_CZ', 'da_DK', 'el_GR', 'es_ES', 'fi_FI', 'hu_HU', 'is_IS', 'it_IT',
		'lt_LT', 'lv_LV', 'nb_NO', 'pl_PL', 'pt_PT', 'sk_SK', 'sl_SI', 'sv_SE', 'tr_TR',
	];

	/**
	 * The gateway's active Mollie methods that accept the order's payment amount, or an empty list when the request fails or the gateway's last request failed in the past minute.
	 *
	 * @return list<MolliePaymentMethod>
	 */
	public function forOrder(Order $order, GatewayInterface $gateway): array
	{
		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		$failureCacheKey = [__METHOD__, 'failed', $gateway->id];

		// Skip the request after a recent failure, since a timed-out request takes five seconds
		if ($cache->get($failureCacheKey)) {
			return [];
		}

		$apiKey = (string) App::parseEnv((string) $gateway->getSettings()['apiKey']);
		$query = $this->query($order);

		try {
			/** @var list<MolliePaymentMethod> */
			return $cache->getOrSet(
				[__METHOD__, $apiKey, $query],
				fn (): array => $this->fetch($apiKey, $query),
				self::CACHE_DURATION
			);
		} catch (GuzzleException|InvalidArgumentException $mollieException) {
			// Skip the failure flag for a rejected query, since this order's amount or country caused it
			if (! $mollieException instanceof ClientException || $mollieException->getResponse()->getStatusCode() === 401) {
				$cache->set($failureCacheKey, true, self::FAILURE_CACHE_DURATION);
			}

			// Log rather than throw, since Mollie's page asks for a method when the payment doesn't name one
			Craft::warning("Unable to list Mollie payment methods for gateway {$gateway->handle}: {$mollieException->getMessage()}", __METHOD__);

			return [];
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function query(Order $order): array
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();
		$currency = $order->getPaymentCurrency();

		// Send the amount, so the list fits the order's currency and amount due
		return array_filter([
			'amount' => [
				'value' => $commerce->getCurrencies()->getTeller($currency)->convertToString($order->getPaymentAmount()),
				'currency' => $currency,
			],
			'billingCountry' => $order->getBillingAddress()?->getCountryCode(),
			'locale' => $this->locale(),
			'include' => 'issuers',
		]);
	}

	/**
	 * @param array<string, mixed> $query
	 * @return list<MolliePaymentMethod>
	 * @throws GuzzleException
	 * @throws InvalidArgumentException
	 */
	private function fetch(string $apiKey, array $query): array
	{
		$response = Craft::createGuzzleClient([
			'timeout' => self::TIMEOUT,
		])->get(self::METHODS_URL, [
			'headers' => [
				'Authorization' => 'Bearer ' . $apiKey,
			],
			'query' => $query,
		]);

		/** @var array{_embedded: array{methods: list<array{id: string, description: string, image: array{svg: string}, issuers?: list<array{id: string, name: string}>}>}} $decoded */
		$decoded = Json::decode((string) $response->getBody());

		$paymentMethods = [];

		foreach ($decoded['_embedded']['methods'] as $method) {
			$paymentMethods[] = [
				'id' => $method['id'],
				'name' => $method['description'],
				'image' => $method['image']['svg'],
				// Skip iDEAL's issuers, since iDEAL 2.0 doesn't take an issuer
				'issuers' => $method['id'] === 'ideal' ? [] : array_map(
					static fn (array $issuer): array => [
						'id' => $issuer['id'],
						'name' => $issuer['name'],
					],
					$method['issuers'] ?? []
				),
			];
		}

		return $paymentMethods;
	}

	/**
	 * The site's language as a Mollie locale, falling back to the language's default region.
	 */
	private function locale(): ?string
	{
		$siteLocale = str_replace('-', '_', Craft::$app->getSites()->getCurrentSite()->language);

		if (in_array($siteLocale, self::LOCALES, true)) {
			return $siteLocale;
		}

		$language = explode('_', $siteLocale)[0];

		foreach (self::LOCALES as $mollieLocale) {
			if (str_starts_with($mollieLocale, $language . '_')) {
				return $mollieLocale;
			}
		}

		return null;
	}
}
