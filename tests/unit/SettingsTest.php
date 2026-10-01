<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\DeliveryDateConfig;
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
				'enablePlaceholderImages' => true,
				'enableSaveForLater' => true,
				'imagerXConfig' => [
					'transformer' => 'craft',
				],
				'enablePageTransitions' => true,
			],
		]);

		self::assertSame([
			'showLineItemSku' => false,
			'enableLineItemOptions' => '_',
			'hiddenLineItemOptionPrefix' => 'x',
			'lineItemOptionValueMaxLength' => 20,
			'enablePlaceholderImages' => true,
			'enableSaveForLater' => true,
			'imagerXConfig' => [
				'transformer' => 'craft',
			],
		], $moved['lineItems']);
		self::assertSame([
			'enablePageTransitions' => true,
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

	/**
	 * The store's country list is only worth reading once a code has been chosen.
	 */
	public function testABlankDefaultCountrySkipsTheStoreLookup(): void
	{
		$settings = new Settings();

		$settings->validateDefaultCountryCode('defaultCountryCode');

		self::assertFalse($settings->hasErrors('defaultCountryCode'));
	}

	public function testSavedAddressesDisplayAsAListByDefault(): void
	{
		$settings = new Settings();

		self::assertSame('list', $settings->savedAddressDisplay);
		self::assertSame(5, $settings->savedAddressDropdownThreshold);
		self::assertTrue($settings->validate(['savedAddressDisplay', 'savedAddressDropdownThreshold']));
	}

	public function testSavedAddressDisplayAcceptsOnlyItsThreeModes(): void
	{
		$settings = new Settings();

		foreach (['list', 'dropdown', 'auto'] as $mode) {
			$settings->savedAddressDisplay = $mode;
			self::assertTrue($settings->validate(['savedAddressDisplay']), $mode);
		}

		$settings->savedAddressDisplay = 'radio';
		self::assertFalse($settings->validate(['savedAddressDisplay']));
	}

	public function testTheDropdownThresholdMustBeAtLeastOne(): void
	{
		$settings = new Settings();
		$settings->savedAddressDropdownThreshold = 0;

		self::assertFalse($settings->validate(['savedAddressDropdownThreshold']));
	}

	public function testTheDisplayModeDecidesWhenSavedAddressesBecomeADropdown(): void
	{
		$settings = new Settings();
		$settings->savedAddressDropdownThreshold = 5;

		$settings->savedAddressDisplay = Settings::SAVED_ADDRESS_DISPLAY_LIST;
		self::assertFalse($settings->usesAddressDropdown(40));

		$settings->savedAddressDisplay = Settings::SAVED_ADDRESS_DISPLAY_DROPDOWN;
		self::assertTrue($settings->usesAddressDropdown(2));

		$settings->savedAddressDisplay = Settings::SAVED_ADDRESS_DISPLAY_AUTO;
		self::assertFalse($settings->usesAddressDropdown(5), 'At the threshold is still a list');
		self::assertTrue($settings->usesAddressDropdown(6));
	}

	public function testAnUnknownDisplayModeFallsBackToTheList(): void
	{
		$settings = new Settings();
		$settings->savedAddressDisplay = 'Dropdown';

		self::assertFalse($settings->usesAddressDropdown(40));
	}

	public function testAConfiguredDeliveryDateIsIgnored(): void
	{
		$options = new OptionConfig([
			'deliveryDate' => [
				'label' => 'Arrives by',
				'display' => true,
			],
		]);

		self::assertEquals(new DeliveryDateConfig(), $options->deliveryDate);
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
			'enableSinglePageCheckout' => true,
		]);

		self::assertTrue($options->enableSinglePageCheckout);
		self::assertFalse($options->hasProperty('enableFreeShippingMessage'));
	}
}
