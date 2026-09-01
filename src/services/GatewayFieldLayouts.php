<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\commerce\elements\Order;
use craft\fieldlayoutelements\CustomField;
use craft\fields\BaseOptionsField;
use craft\fields\Dropdown;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use yii\base\Component;

/**
 * Field layouts service.
 *
 * Layouts are kept in project config, never in the `fieldlayouts` table, because Craft looks that
 * table up by element type and a stored row could come back as the order's own layout.
 *
 * @phpstan-type RenderableField array{handle: string, label: string, instructions: ?string, required: bool, width: int, type: string, placeholder: ?string, maxLength: ?int, min: ?int, max: ?int, initialRows: ?int, options: list<array{label: string, value: string}>}
 */
class GatewayFieldLayouts extends Component
{
	public const CONFIG_KEY = 'foster-checkout.gatewayFieldLayouts';

	public function getFieldLayout(string $gatewayHandle): FieldLayout
	{
		$stored = $this->storedLayout($gatewayHandle);

		if ($stored === null) {
			return new FieldLayout([
				'type' => Order::class,
			]);
		}

		$config = reset($stored);
		$layout = FieldLayout::createFromConfig(is_array($config) ? $config : []);
		$layout->uid = (string) key($stored);
		// The designer asks the type for its thumb settings, and these are order fields.
		$layout->type = Order::class;

		return $layout;
	}

	/**
	 * @return array<int, RenderableField>
	 */
	public function getRenderableFields(string $gatewayHandle, ?Order $order = null): array
	{
		return $this->renderableFields($this->getFieldLayout($gatewayHandle), $order);
	}

	/**
	 * Fields a layout asks for, flattened for the storefront.
	 *
	 * Type and bounds come from the Craft field, which a layout cannot express.
	 *
	 * @return array<int, RenderableField>
	 */
	public function renderableFields(FieldLayout $layout, ?ElementInterface $element = null): array
	{
		$fields = [];

		// Without an element there is nothing to test a visibility condition against, so list them all
		$layoutElements = $element instanceof ElementInterface
			? $layout->getVisibleCustomFieldElements($element)
			: $layout->getCustomFieldElements();

		foreach ($layoutElements as $layoutElement) {
			$field = $this->renderableField($layoutElement);

			if ($field !== null) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * @return RenderableField|null null where the plugin has no input for the field's type
	 */
	public function renderableField(CustomField $layoutElement): ?array
	{
		$field = $layoutElement->getField();
		$inputType = $this->fieldInputType($field);

		// A layout saved before the type check existed can still hold a field with no input to render
		if ($inputType === null) {
			return null;
		}

		return [
			'handle' => (string) $field->handle,
			'label' => $layoutElement->label ?? (string) $field->name,
			'instructions' => $layoutElement->instructions,
			'required' => $layoutElement->required,
			'width' => $layoutElement->width,
			'type' => $inputType,
			'placeholder' => $field instanceof PlainText ? $field->placeholder : null,
			'maxLength' => $field instanceof PlainText ? $field->charLimit : null,
			'min' => $field instanceof Number ? (int) $field->min : null,
			'max' => $field instanceof Number && $field->max !== null ? (int) $field->max : null,
			'initialRows' => $field instanceof PlainText ? $field->initialRows : null,
			'options' => $this->fieldOptions($field),
		];
	}

	public function fieldInputType(FieldInterface $field): ?string
	{
		return match (true) {
			$field instanceof PlainText => $field->multiline ? 'textarea' : 'text',
			$field instanceof Number => 'number',
			$field instanceof Dropdown => 'select',
			$field instanceof RadioButtons => 'radio',
			default => null,
		};
	}

	/**
	 * Handles of every field the order can actually store, so a layout cannot be saved with a field
	 * whose value the order would silently discard.
	 *
	 * @return array<int, string>
	 */
	public function orderFieldHandles(): array
	{
		$orderLayout = Craft::$app->getFields()->getLayoutByType(Order::class);

		if (! $orderLayout instanceof FieldLayout) {
			return [];
		}

		$handles = [];

		foreach ($orderLayout->getCustomFieldElements() as $customField) {
			$handle = $customField->getField()->handle;

			if (is_string($handle)) {
				$handles[] = $handle;
			}
		}

		return $handles;
	}

	public function saveFieldLayout(string $gatewayHandle, FieldLayout $layout): bool
	{
		if (! $layout->validate()) {
			return false;
		}

		$layout->uid ??= StringHelper::UUID();
		$layout->type = Order::class;

		Craft::$app->getProjectConfig()->set(self::CONFIG_KEY . '.' . $gatewayHandle, [
			$layout->uid => $layout->getConfig() ?? [],
		]);

		return true;
	}

	/**
	 * @return list<array{label: string, value: string}>
	 */
	private function fieldOptions(FieldInterface $field): array
	{
		if (! $field instanceof BaseOptionsField) {
			return [];
		}

		$options = [];

		foreach ($field->options ?? [] as $option) {
			// An optgroup row carries no value of its own, so its options are listed flat
			if (isset($option['optgroup'])) {
				continue;
			}

			$options[] = [
				// Craft runs option labels through the site category in a protected method
				'label' => Craft::t('site', (string) $option['label']),
				'value' => (string) $option['value'],
			];
		}

		return $options;
	}

	/**
	 * @return array<string, array<string, mixed>>|null
	 */
	private function storedLayout(string $gatewayHandle): ?array
	{
		$stored = Craft::$app->getProjectConfig()->get(self::CONFIG_KEY . '.' . $gatewayHandle);

		if (! is_array($stored) || $stored === []) {
			return null;
		}

		/** @var array<string, array<string, mixed>> $stored */
		return $stored;
	}
}
