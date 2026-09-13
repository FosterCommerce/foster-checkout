<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\LineItemConfig;
use fostercommerce\fostercheckout\models\OptionConfig;
use fostercommerce\fostercheckout\models\PathConfig;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class SettingsTest extends CheckoutTestCase
{
	public function testEveryConfigNodeIsBuiltWithoutConfig(): void
	{
		$settings = new Settings();

		self::assertInstanceOf(OptionConfig::class, $settings->options);
		self::assertInstanceOf(LineItemConfig::class, $settings->lineItems);
		self::assertInstanceOf(PathConfig::class, $settings->paths);
		self::assertSame([], $settings->notShippableProducts);
		self::assertSame([], $settings->requiredAddressFields);
		self::assertSame([], $settings->requiredBillingAddressFields);
		self::assertSame([], $settings->hiddenBillingAddressFields);
		self::assertFalse($settings->enableCustomerPickup);
	}

	public function testNestedConfigBecomesItsOwnModel(): void
	{
		$settings = new Settings();
		$settings->setAttributes([
			'paths' => [
				'cart' => '/shop/cart/',
			],
			'options' => [
				'enableSinglePageCheckout' => true,
			],
		], false);

		self::assertSame('shop/cart', $settings->paths->cart);
		self::assertTrue($settings->options->enableSinglePageCheckout);
		self::assertTrue($settings->options->isSinglePageCheckout());
	}

	public function testLineItemSettingsMoveOutOfOptions(): void
	{
		$moved = Settings::moveLineItemSettings([
			'options' => [
				'showLineItemSku' => false,
				'enableLineItemOptions' => '_',
				'hiddenLineItemOptionPrefix' => 'x',
				'lineItemOptionValueMaxLength' => 20,
				'enableSaveForLater' => true,
			],
		]);

		self::assertSame([
			'showLineItemSku' => false,
			'enableLineItemOptions' => '_',
			'hiddenLineItemOptionPrefix' => 'x',
			'lineItemOptionValueMaxLength' => 20,
		], $moved['lineItems']);
		self::assertSame([
			'enableSaveForLater' => true,
		], $moved['options']);
	}

	/**
	 * A config file naming the old place must not beat a value already stored in the new one.
	 */
	public function testAStoredLineItemSettingBeatsTheOneStillInOptions(): void
	{
		$moved = Settings::moveLineItemSettings([
			'options' => [
				'showLineItemSku' => false,
			],
			'lineItems' => [
				'showLineItemSku' => true,
			],
		]);

		self::assertSame([
			'showLineItemSku' => true,
		], $moved['lineItems']);
	}

	public function testValuesWithoutAnOptionsNodeAreUntouched(): void
	{
		$values = [
			'lineItems' => [
				'showLineItemSku' => true,
			],
		];

		self::assertSame($values, Settings::moveLineItemSettings($values));
	}

	public function testAnEmptyProductConditionMatchesNothing(): void
	{
		$settings = new Settings();

		self::assertSame([], $settings->getNotShippableProductsCondition()->getConditionRules());
	}

	/**
	 * Dropping them keeps a `devMode` site from throwing on the deprecation.
	 */
	public function testRemovedOptionSettingsAreDroppedRatherThanSet(): void
	{
		$options = new OptionConfig([
			'enableFreeShippingMessage' => true,
			'enableMadeAMistake' => true,
			'enableSaveForLater' => true,
		]);

		self::assertTrue($options->enableSaveForLater);
		self::assertFalse($options->hasProperty('enableFreeShippingMessage'));
	}
}
