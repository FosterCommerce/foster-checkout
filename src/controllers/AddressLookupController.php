<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\web\Controller;
use fostercommerce\fostercheckout\base\AddressLookupInterface;
use fostercommerce\fostercheckout\FosterCheckout;
use Throwable;
use yii\web\Response;

/**
 * Proxies address lookups so the provider key stays in the environment.
 */
class AddressLookupController extends Controller
{
	public array|bool|int $allowAnonymous = true;

	public function actionSuggest(): Response
	{
		$this->requireAcceptsJson();
		$this->requirePostRequest();

		$provider = $this->provider();

		if (! $provider instanceof AddressLookupInterface) {
			return $this->asJson([
				'suggestions' => [],
			]);
		}

		$container = $this->bodyString('container');

		try {
			$suggestions = $provider->suggest(
				$this->bodyString('query'),
				$this->bodyString('countryCode'),
				$container === '' ? null : $container,
				$this->bodyString('session'),
			);
		} catch (Throwable $throwable) {
			return $this->asJson([
				'suggestions' => $this->failed($throwable),
			]);
		}

		return $this->asJson([
			'suggestions' => array_map(static fn ($suggestion): array => $suggestion->toArray(), $suggestions),
		]);
	}

	public function actionRetrieve(): Response
	{
		$this->requireAcceptsJson();
		$this->requirePostRequest();

		$provider = $this->provider();

		if (! $provider instanceof AddressLookupInterface) {
			return $this->asJson([
				'address' => null,
			]);
		}

		try {
			$address = $provider->retrieve(
				$this->bodyString('id'),
				$this->bodyString('session'),
			);
		} catch (Throwable $throwable) {
			$this->failed($throwable);

			return $this->asJson([
				'address' => null,
			]);
		}

		return $this->asJson([
			'address' => $address === [] ? null : $address,
		]);
	}

	/**
	 * A dead provider leaves the customer typing the address, so the reason is only logged.
	 *
	 * @return array<never>
	 */
	private function failed(Throwable $throwable): array
	{
		Craft::warning('Address lookup failed: ' . $throwable->getMessage(), 'foster-checkout');

		return [];
	}

	private function bodyString(string $name): string
	{
		$posted = $this->request->getBodyParam($name);

		return is_string($posted) ? trim($posted) : '';
	}

	private function provider(): ?AddressLookupInterface
	{
		return FosterCheckout::getInstance()?->getAddressLookup()->provider();
	}
}
