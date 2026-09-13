<?php

namespace fostercommerce\fostercheckout\tests\unit;

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
}
