<?php

namespace fostercommerce\fostercheckout\tests\unit;

use Craft;
use craft\services\Plugins;
use fostercommerce\fostercheckout\models\LineItemConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LineItemConfigTest extends CheckoutTestCase
{
	public function testDefaultsShowEverythingButUnderscoredOptions(): void
	{
		$config = new LineItemConfig();

		self::assertTrue($config->showLineItemSku);
		self::assertTrue($config->showLineItemStock);
		self::assertTrue($config->enableLineItemOptions);
		self::assertSame('_', $config->hiddenLineItemOptionPrefix);
		self::assertNull($config->lineItemOptionValueMaxLength);
	}

	/**
	 * The setting was a toggle and a hidden-name prefix in one value until 1.1.0.
	 */
	#[DataProvider('legacyOptionValues')]
	public function testLegacyOptionValueSplitsIntoToggleAndPrefix(string $posted, bool $expectedEnabled, string $expectedPrefix): void
	{
		$config = new LineItemConfig([
			'enableLineItemOptions' => $posted,
		]);

		self::assertSame($expectedEnabled, $config->enableLineItemOptions);
		self::assertSame($expectedPrefix, $config->hiddenLineItemOptionPrefix);
	}

	/**
	 * @return array<string, array{0: string, 1: bool, 2: string}>
	 */
	public static function legacyOptionValues(): array
	{
		return [
			'switch on' => ['1', true, '_'],
			'switch off' => ['0', false, '_'],
			'empty switch' => ['', false, '_'],
			'underscore prefix' => ['_', true, '_'],
			'word prefix' => ['hidden:', true, 'hidden:'],
		];
	}

	public function testAPostedPrefixBeatsTheLegacyValue(): void
	{
		$config = new LineItemConfig([
			'enableLineItemOptions' => 'hidden:',
			'hiddenLineItemOptionPrefix' => '__',
		]);

		self::assertTrue($config->enableLineItemOptions);
		self::assertSame('__', $config->hiddenLineItemOptionPrefix);
	}

	public function testABooleanIsLeftAlone(): void
	{
		$config = new LineItemConfig([
			'enableLineItemOptions' => false,
			'hiddenLineItemOptionPrefix' => 'x',
		]);

		self::assertFalse($config->enableLineItemOptions);
		self::assertSame('x', $config->hiddenLineItemOptionPrefix);
	}

	public function testImageTransformParamsAreTypedAndReadFromTheEnvironment(): void
	{
		putenv('FC_TEST_WATERMARK=/watermark.png');

		$config = new LineItemConfig([
			'imageTransformConfig' => [
				'allowUpscale' => 'false',
				'quality' => '80',
				'watermarkWidth' => '66w',
				'transformerParams' => [
					'watermarkPath' => '$FC_TEST_WATERMARK',
				],
			],
		]);

		try {
			self::assertSame([
				'allowUpscale' => false,
				'quality' => 80,
				'watermarkWidth' => '66w',
				'transformerParams' => [
					'watermarkPath' => '/watermark.png',
				],
			], $config->imageTransformParams());
		} finally {
			putenv('FC_TEST_WATERMARK');
		}
	}

	public function testParamsTableRowsBecomeAMapOfNamedParams(): void
	{
		self::assertSame([
			'watermarkPath' => '/watermark.png',
			'watermarkAlpha' => '41',
		], LineItemConfig::imageTransformConfigFromRows([
			'new1' => [
				'name' => ' watermarkPath ',
				'value' => ' /watermark.png ',
			],
			'new2' => [
				'name' => '',
				'value' => 'ignored',
			],
			'new3' => [
				'name' => 'watermarkAlpha',
				'value' => '41',
			],
		]));
	}

	/**
	 * Expect an empty string when every row of the table is deleted.
	 */
	public function testAnEmptyParamsTableBecomesAnEmptyMap(): void
	{
		self::assertSame([], LineItemConfig::imageTransformConfigFromRows(''));
	}

	/**
	 * @param list<string> $enabledPluginHandles
	 */
	#[DataProvider('imageTransformers')]
	public function testImageTransformerInUseFollowsTheEnabledPlugins(array $enabledPluginHandles, string $imageTransformer, string $expected): void
	{
		$config = new LineItemConfig([
			'imageTransformer' => $imageTransformer,
		]);

		self::assertSame($expected, $this->withEnabledPlugins($enabledPluginHandles, $config->imageTransformerInUse(...)));
	}

	/**
	 * @return array<string, array{list<string>, string, string}>
	 */
	public static function imageTransformers(): array
	{
		return [
			'auto with Imager X' => [['imager-x'], 'auto', 'imager-x'],
			'auto with Imager X and Small Pics' => [['imager-x', 'smallpics'], 'auto', 'craft'],
			'auto with Small Pics' => [['smallpics'], 'auto', 'craft'],
			'craft with both plugins' => [['imager-x', 'smallpics'], 'craft', 'craft'],
			'imager-x enabled' => [['imager-x'], 'imager-x', 'imager-x'],
			'smallpics enabled' => [['smallpics'], 'smallpics', 'smallpics'],
			'smallpics disabled' => [[], 'smallpics', 'craft'],
		];
	}

	public function testADisabledImageTransformerIsLoggedOnce(): void
	{
		$config = new LineItemConfig([
			'imageTransformer' => 'smallpics',
		]);

		$warningCount = fn (): int => count(array_filter(
			Craft::getLogger()->messages,
			static fn (array $message): bool => is_string($message[0]) && str_contains($message[0], '`smallpics` is not an enabled plugin'),
		));
		$warningsBefore = $warningCount();

		$this->withEnabledPlugins([], static function () use ($config): void {
			$config->imageTransformerInUse();
			$config->imageTransformerInUse();
		});

		self::assertSame($warningsBefore + 1, $warningCount());
	}

	public function testTheDeprecatedImagerXConfigKeySetsTheImageTransformConfig(): void
	{
		$config = new LineItemConfig([
			'imagerXConfig' => [
				'mode' => 'crop',
			],
		]);

		self::assertSame([
			'mode' => 'crop',
		], $config->imageTransformConfig);
	}

	public function testTheDeprecatedImagerXConfigReturnsTheImageTransformConfig(): void
	{
		$config = new LineItemConfig([
			'imageTransformConfig' => [
				'mode' => 'crop',
			],
		]);

		self::assertSame([
			'mode' => 'crop',
		], $config->getImagerXConfig());
	}

	/**
	 * Run the callback with only these plugins enabled, since the suite runs on sites with different plugins installed.
	 *
	 * @param list<string> $enabledPluginHandles
	 */
	private function withEnabledPlugins(array $enabledPluginHandles, callable $callback): mixed
	{
		$plugins = Craft::$app->getPlugins();

		Craft::$app->set('plugins', new class($enabledPluginHandles) extends Plugins {
			/**
			 * @param list<string> $enabledPluginHandles
			 */
			public function __construct(
				private readonly array $enabledPluginHandles,
			) {
				parent::__construct();
			}

			#[\Override]
			public function isPluginEnabled(string $handle): bool
			{
				return in_array($handle, $this->enabledPluginHandles, true);
			}
		});

		try {
			return $callback();
		} finally {
			Craft::$app->set('plugins', $plugins);
		}
	}
}
