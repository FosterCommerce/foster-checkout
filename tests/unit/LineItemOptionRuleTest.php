<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\LineItemOptionRule;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class LineItemOptionRuleTest extends CheckoutTestCase
{
	public function testARuleWithoutAUidIsGivenOne(): void
	{
		$rule = new LineItemOptionRule();

		self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $rule->uid);
	}

	public function testAPostedUidIsKeptSoEditsKeepTheirTarget(): void
	{
		$rule = new LineItemOptionRule([
			'uid' => 'ee6a2c2c-5f5b-4c0b-9c2e-2c4f0a1b2c3d',
		]);

		self::assertSame('ee6a2c2c-5f5b-4c0b-9c2e-2c4f0a1b2c3d', $rule->uid);
	}

	public function testAnEmptyUidIsReplaced(): void
	{
		$rule = new LineItemOptionRule([
			'uid' => '',
		]);

		self::assertNotSame('', $rule->uid);
	}

	public function testNameAndValueAreLeftNullSoARuleCanRewriteOnePart(): void
	{
		$rule = new LineItemOptionRule([
			'setValue' => 'Gift wrapped',
		]);

		self::assertNull($rule->setName);
		self::assertSame('Gift wrapped', $rule->setValue);
	}

	/**
	 * The condition is not a public attribute, so settings storage would otherwise drop it.
	 */
	public function testTheConditionSurvivesSerialization(): void
	{
		$rule = new LineItemOptionRule([
			'setName' => 'Wrap',
		]);

		self::assertArrayHasKey('condition', $rule->toArray());
		self::assertSame($rule->getCondition()->getConfig(), $rule->toConfig()['condition']);
	}

	public function testToConfigCarriesEveryStoredPart(): void
	{
		$rule = new LineItemOptionRule([
			'setName' => 'Wrap',
			'setValue' => 'Yes',
		]);

		self::assertSame(['uid', 'condition', 'setName', 'setValue'], array_keys($rule->toConfig()));
		self::assertSame('Wrap', $rule->toConfig()['setName']);
	}
}
