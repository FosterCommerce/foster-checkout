<?php

namespace fostercommerce\fostercheckout\tests\integration;

use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

/**
 * The shipping and billing required lists, split apart in 1.5.0.
 *
 * @phpstan-import-type AddressFormElement from Checkout
 */
final class AddressFieldsTest extends CheckoutTestCase
{
	public function testTheFormCarriesTheOrderAndWidthsTheLayoutSets(): void
	{
		$fields = $this->checkout()->addressFields();

		self::assertNotSame([], $fields);

		foreach ($fields as $field) {
			self::assertSame(['type', 'required', 'width', 'field'], array_keys($field));
			self::assertIsBool($field['required']);
			self::assertIsInt($field['width']);
		}
	}

	public function testTheCountryAndAddressBlockAreNeverConfigurable(): void
	{
		$configurable = array_column($this->checkout()->configurableAddressFields(), 'value');

		self::assertNotContains('countryCode', $configurable);
		self::assertNotContains('addressLine1', $configurable);
	}

	public function testARequiredShippingFieldIsNotRequiredOnBilling(): void
	{
		$attribute = $this->firstConfigurableAttribute();
		$settings = $this->settings();
		$originalShipping = $settings->requiredAddressFields;
		$originalBilling = $settings->requiredBillingAddressFields;

		$settings->requiredAddressFields = [$attribute];
		$settings->requiredBillingAddressFields = [];

		try {
			self::assertTrue($this->isRequired($this->checkout()->addressFields(), $attribute));
			self::assertFalse($this->isRequired($this->checkout()->addressFields(null, false, 'billingAddress'), $attribute));
		} finally {
			$settings->requiredAddressFields = $originalShipping;
			$settings->requiredBillingAddressFields = $originalBilling;
		}
	}

	public function testARequiredBillingFieldIsNotRequiredOnShipping(): void
	{
		$attribute = $this->firstConfigurableAttribute();
		$settings = $this->settings();
		$originalShipping = $settings->requiredAddressFields;
		$originalBilling = $settings->requiredBillingAddressFields;

		$settings->requiredAddressFields = [];
		$settings->requiredBillingAddressFields = [$attribute];

		try {
			self::assertFalse($this->isRequired($this->checkout()->addressFields(), $attribute));
			self::assertTrue($this->isRequired($this->checkout()->addressFields(null, false, 'billingAddress'), $attribute));
		} finally {
			$settings->requiredAddressFields = $originalShipping;
			$settings->requiredBillingAddressFields = $originalBilling;
		}
	}

	public function testAFieldHiddenOnBillingStaysOnShipping(): void
	{
		$attribute = $this->firstConfigurableAttribute();
		$settings = $this->settings();
		$originalHidden = $settings->hiddenBillingAddressFields;
		$settings->hiddenBillingAddressFields = [$attribute];

		try {
			self::assertTrue($this->isPresent($this->checkout()->addressFields(), $attribute));
			self::assertFalse($this->isPresent($this->checkout()->addressFields(null, false, 'billingAddress'), $attribute));
		} finally {
			$settings->hiddenBillingAddressFields = $originalHidden;
		}
	}

	public function testAFieldHiddenEverywhereIsOffBothForms(): void
	{
		$attribute = $this->firstConfigurableAttribute();
		$settings = $this->settings();
		$originalHidden = $settings->hiddenAddressFields;
		$settings->hiddenAddressFields = [$attribute];

		try {
			self::assertFalse($this->isPresent($this->checkout()->addressFields(), $attribute));
			self::assertFalse($this->isPresent($this->checkout()->addressFields(null, false, 'billingAddress'), $attribute));
		} finally {
			$settings->hiddenAddressFields = $originalHidden;
		}
	}

	/**
	 * Read the store's own lists, rather than a pair this test set.
	 */
	public function testTheStoresConfiguredListsAreApplied(): void
	{
		$settings = $this->settings();

		if ($settings->hiddenBillingAddressFields === [] && $settings->requiredBillingAddressFields === []) {
			self::markTestSkipped('This site configures no billing address fields of its own.');
		}

		$billingForm = $this->checkout()->addressFields(null, false, 'billingAddress');

		foreach ($settings->hiddenBillingAddressFields as $attribute) {
			self::assertFalse($this->isPresent($billingForm, $attribute), "{$attribute} is still on the billing form");
		}

		foreach ($settings->requiredBillingAddressFields as $attribute) {
			if ($this->isPresent($billingForm, $attribute)) {
				self::assertTrue($this->isRequired($billingForm, $attribute), "{$attribute} is not required on the billing form");
			}
		}
	}

	/**
	 * Offer the name only where it is saved, since Commerce retitles an order address on save.
	 */
	public function testTheAddressNameIsOnlyOfferedWhereItIsSaved(): void
	{
		$settings = $this->settings();
		$original = $settings->showAddressLabelField;
		$settings->showAddressLabelField = true;

		try {
			self::assertContains('label', array_column($this->checkout()->addressFields(null, true), 'type'));
			self::assertNotContains('label', array_column($this->checkout()->addressFields(), 'type'));
		} finally {
			$settings->showAddressLabelField = $original;
		}
	}

	public function testTheAddressNameIsOffUntilTheSettingIsTurnedOn(): void
	{
		$settings = $this->settings();
		$original = $settings->showAddressLabelField;
		$settings->showAddressLabelField = false;

		try {
			self::assertNotContains('label', array_column($this->checkout()->addressFields(null, true), 'type'));
		} finally {
			$settings->showAddressLabelField = $original;
		}
	}

	/**
	 * @param array<int, AddressFormElement> $fields
	 */
	private function isRequired(array $fields, string $attribute): bool
	{
		foreach ($fields as $field) {
			if ($this->attributeOf($field) === $attribute) {
				return $field['required'];
			}
		}

		return false;
	}

	/**
	 * @param array<int, AddressFormElement> $fields
	 */
	private function isPresent(array $fields, string $attribute): bool
	{
		foreach ($fields as $field) {
			if ($this->attributeOf($field) === $attribute) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The settings screens name a custom address field by its handle and a native one by its attribute.
	 *
	 * @param AddressFormElement $field
	 */
	private function attributeOf(array $field): string
	{
		if ($field['type'] !== 'custom') {
			return $field['type'];
		}

		return $field['field']['handle'] ?? '';
	}

	/**
	 * Only a field the layout leaves optional can be hidden or required from the settings screen.
	 */
	private function firstConfigurableAttribute(): string
	{
		$fields = $this->checkout()->addressFields();
		$attributes = array_map(fn (array $field): string => $this->attributeOf($field), $fields);

		foreach ($this->checkout()->configurableAddressFields() as $option) {
			if (in_array($option['value'], $attributes, true)) {
				return $option['value'];
			}
		}

		self::markTestSkipped('Every address field on this site is required by the layout.');
	}
}
