<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\ValueConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;
use yii\base\InvalidConfigException;

final class ValueConfigTest extends CheckoutTestCase
{
	public function testAStringIsRenderedAsATemplate(): void
	{
		$value = new ValueConfig('Hello {{ name }}');

		self::assertSame('Hello world', $value->toStringWithContext([
			'name' => 'world',
		]));
	}

	public function testACallableReceivesTheContext(): void
	{
		$value = new ValueConfig();
		$value->value = static fn (array $context): string => 'order ' . $context['number'];

		self::assertSame('order abc123', $value->toStringWithContext([
			'number' => 'abc123',
		]));
	}

	public function testAnEmptyConfigStringifiesToNothing(): void
	{
		self::assertSame('', (new ValueConfig())->toStringWithContext());
	}

	public function testAnArrayOfLinksIsReducedToTextAndUrl(): void
	{
		$value = new ValueConfig();
		$value->value = [
			[
				'text' => 'Terms',
				'url' => '/terms',
				'target' => '_blank',
			],
		];

		self::assertSame([[
			'text' => 'Terms',
			'url' => '/terms',
		]], $value->getValue());
	}

	public function testAnArrayThatIsNotLinksIsRejected(): void
	{
		$value = new ValueConfig();
		$value->value = ['just', 'strings'];

		$this->expectException(InvalidConfigException::class);
		$value->getValue();
	}

	public function testAnElementConfigNeedsBothHandles(): void
	{
		$this->expectException(InvalidConfigException::class);
		new ValueConfig([
			'elementHandle' => 'siteInfo',
		]);
	}

	public function testFromConfigReturnsAnEmptyValueWhenTheKeyIsAbsent(): void
	{
		self::assertSame('', (string) ValueConfig::fromConfig('subscribe', []));
	}

	public function testFromConfigReadsTheNamedKey(): void
	{
		self::assertSame('Join us', (string) ValueConfig::fromConfig('subscribe', [
			'subscribe' => 'Join us',
		]));
	}
}
