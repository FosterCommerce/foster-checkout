<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\DeliveryDateConfig;
use fostercommerce\fostercheckout\models\ValueConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class DeliveryDateConfigTest extends CheckoutTestCase
{
	public function testEveryPartIsAValueConfigWithoutConfig(): void
	{
		$config = new DeliveryDateConfig();

		foreach (['label', 'message', 'estimate', 'display'] as $part) {
			self::assertInstanceOf(ValueConfig::class, $config->{$part});
			self::assertSame('', (string) $config->{$part});
		}
	}

	public function testAStringPartBecomesARenderableValue(): void
	{
		$config = new DeliveryDateConfig([
			'label' => 'Arrives {{ day }}',
		]);

		self::assertSame('Arrives Tuesday', $config->label->toStringWithContext([
			'day' => 'Tuesday',
		]));
		self::assertSame('', (string) $config->message);
	}
}
