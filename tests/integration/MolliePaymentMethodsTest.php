<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\gateways\Dummy;
use fostercommerce\fostercheckout\services\MolliePaymentMethods;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use yii\caching\CacheInterface;

/**
 * @since 2.1.0
 */
final class MolliePaymentMethodsTest extends CheckoutTestCase
{
	private string $siteLanguage;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->siteLanguage = Craft::$app->getSites()->getCurrentSite()->language;
		$this->plugin();
	}

	#[\Override]
	protected function tearDown(): void
	{
		Craft::$app->getSites()->getCurrentSite()->language = $this->siteLanguage;

		parent::tearDown();
	}

	/**
	 * @return array<string, array{string, ?string}>
	 */
	public static function siteLanguages(): array
	{
		return [
			'a listed locale' => ['de-CH', 'de_CH'],
			'an unlisted region' => ['en-CA', 'en_US'],
			'a bare language' => ['nl', 'nl_NL'],
			'an unlisted language' => ['ja-JP', null],
		];
	}

	#[DataProvider('siteLanguages')]
	public function testTheSiteLanguageBecomesAMollieLocale(string $siteLanguage, ?string $mollieLocale): void
	{
		Craft::$app->getSites()->getCurrentSite()->language = $siteLanguage;

		$locale = new ReflectionMethod(MolliePaymentMethods::class, 'locale');

		self::assertSame($mollieLocale, $locale->invoke($this->plugin()->getMolliePaymentMethods()));
	}

	public function testARecentFailureSkipsTheRequest(): void
	{
		// Throw on any read of the API key, since a failed request would also return an empty list
		$gateway = new class() extends Dummy {
			/**
			 * @return array<string, mixed>
			 */
			#[\Override]
			public function getSettings(): array
			{
				throw new LogicException('The request was not skipped.');
			}
		};
		$gateway->id = 999999;
		$gateway->handle = 'mollieTest';

		/** @var CacheInterface $cache */
		$cache = Craft::$app->getCache();
		$failureCacheKey = [MolliePaymentMethods::class . '::forOrder', 'failed', $gateway->id];
		$cache->set($failureCacheKey, true, 60);

		try {
			self::assertSame([], $this->plugin()->getMolliePaymentMethods()->forOrder(new Order(), $gateway));
		} finally {
			$cache->delete($failureCacheKey);
		}
	}
}
