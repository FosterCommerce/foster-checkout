<?php

namespace fostercommerce\fostercheckout\migrations;

use Craft;
use craft\commerce\elements\Order;
use craft\db\Migration;
use craft\fields\PlainText;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use fostercommerce\fostercheckout\FosterCheckout;

/**
 * Converts the per-gateway `fields` config into a field layout per gateway.
 *
 * The old config described fields the order already had, so it duplicated what Craft stores. A
 * layout cannot express a placeholder or a length limit, so those move onto the field itself.
 */
class m260814_022939_convert_gateway_fields_to_layouts extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$projectConfig = Craft::$app->getProjectConfig();
		$readOnly = $projectConfig->readOnly;
		$projectConfig->readOnly = false;

		try {
			return $this->convertGatewayFields();
		} finally {
			$projectConfig->readOnly = $readOnly;
		}
	}

	private function convertGatewayFields(): bool
	{
		$fileConfig = Craft::$app->getConfig()->getConfigFromFile(FosterCheckout::HANDLE);
		$gateways = is_array($fileConfig) && is_array($fileConfig['paymentGateways'] ?? null)
			? $fileConfig['paymentGateways']
			: [];

		$plugin = FosterCheckout::getInstance();

		if (! $plugin instanceof FosterCheckout) {
			return true;
		}

		foreach ($gateways as $gatewayHandle => $gateway) {
			if (! is_string($gatewayHandle)) {
				continue;
			}

			if (! is_array($gateway)) {
				continue;
			}

			if (! is_array($gateway['fields'] ?? null)) {
				continue;
			}

			// Anything already configured in the control panel wins, so re-running is safe.
			if ($plugin->checkoutFieldLayouts->getFieldLayout($gatewayHandle)->getCustomFieldElements() !== []) {
				continue;
			}

			$layoutElements = [];

			foreach ($gateway['fields'] as $fieldHandle => $fieldConfig) {
				if (! is_string($fieldHandle)) {
					continue;
				}

				if (! is_array($fieldConfig)) {
					continue;
				}

				$field = Craft::$app->getFields()->getFieldByHandle($fieldHandle);

				// A handle with no matching field never saved a value, so there is nothing to convert.
				if ($field === null) {
					continue;
				}

				$this->copyInputSettingsToField($field, $fieldConfig);

				$layoutElement = new \craft\fieldlayoutelements\CustomField($field);
				$layoutElement->required = (bool) ($fieldConfig['required'] ?? false);
				$layoutElement->width = $this->width($fieldConfig, $gateway);

				$label = trim((string) ($fieldConfig['label'] ?? ''));

				if ($label !== '' && $label !== $field->name) {
					$layoutElement->label = $label;
				}

				$layoutElements[] = $layoutElement;
			}

			if ($layoutElements === []) {
				continue;
			}

			// A tab resolves its elements through its layout, so it has to know the layout first.
			$layout = new FieldLayout([
				'type' => Order::class,
			]);

			$tab = new FieldLayoutTab([
				'layout' => $layout,
				'name' => 'Content',
				'elements' => $layoutElements,
			]);

			$layout->setTabs([$tab]);

			$plugin->checkoutFieldLayouts->saveFieldLayout($gatewayHandle, $layout);
		}

		return true;
	}

	/**
	 * @param array<array-key, mixed> $fieldConfig
	 */
	private function copyInputSettingsToField(\craft\base\FieldInterface $field, array $fieldConfig): void
	{
		if (! $field instanceof PlainText) {
			return;
		}

		$postedPlaceholder = $fieldConfig['placeholder'] ?? '';
		$placeholder = is_string($postedPlaceholder) ? trim($postedPlaceholder) : '';
		$maxLength = $fieldConfig['maxLength'] ?? false;
		$changed = false;

		if ($placeholder !== '' && ($field->placeholder ?? '') === '') {
			$field->placeholder = $placeholder;
			$changed = true;
		}

		if (is_int($maxLength) && $field->charLimit === null) {
			$field->charLimit = $maxLength;
			$changed = true;
		}

		if ($changed) {
			Craft::$app->getFields()->saveField($field);
		}
	}

	/**
	 * The old config spanned a field across a gateway's columns; a layout stores a percentage.
	 *
	 * @param array<array-key, mixed> $fieldConfig
	 * @param array<array-key, mixed> $gateway
	 */
	private function width(array $fieldConfig, array $gateway): int
	{
		$gatewayColumns = is_numeric($gateway['columns'] ?? null) ? (int) $gateway['columns'] : 3;
		$span = is_numeric($fieldConfig['columns'] ?? null) ? (int) $fieldConfig['columns'] : 1;

		if ($gatewayColumns < 1) {
			return 100;
		}

		$percentage = (int) round($span / $gatewayColumns * 100 / 25) * 25;

		return max(25, min(100, $percentage));
	}
}
