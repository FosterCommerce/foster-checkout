<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
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
class CheckoutFieldLayouts extends Component
{
	// Renaming this path would orphan every layout already stored under it.
	public const CONFIG_KEY = 'foster-checkout.gatewayFieldLayouts';

	// A gateway can be handled 'billing', so checkout layouts are stored apart from gateway ones.
	public const CHECKOUT_CONFIG_KEY = 'foster-checkout.checkoutFieldLayouts';

	/**
	 * Where in the checkout a layout's fields render.
	 */
	public const CHECKOUT_POSITIONS = ['email', 'shippingAddress', 'shippingMethod', 'billing', 'summary'];

	public function getFieldLayout(string $gatewayHandle): FieldLayout
	{
		return $this->layoutAt(self::CONFIG_KEY, $gatewayHandle);
	}

	public function getCheckoutFieldLayout(string $position): FieldLayout
	{
		return $this->layoutAt(self::CHECKOUT_CONFIG_KEY, $position);
	}

	/**
	 * @return array<int, RenderableField>
	 */
	public function getRenderableFields(string $gatewayHandle, ?Order $order = null): array
	{
		return $this->renderableFields($this->getFieldLayout($gatewayHandle), $order);
	}

	/**
	 * @return array<int, RenderableField>
	 */
	public function getRenderableCheckoutFields(string $position, ?Order $order = null): array
	{
		return $this->renderableFields($this->getCheckoutFieldLayout($position), $order);
	}

	/**
	 * Handles already claimed by another layout.
	 *
	 * Two layouts holding one handle render two inputs posting the same name, and the last one wins.
	 *
	 * @return list<string>
	 */
	public function claimedFieldHandles(string $exceptPosition): array
	{
		$handles = [];

		foreach (self::CHECKOUT_POSITIONS as $position) {
			if ($position === $exceptPosition) {
				continue;
			}

			foreach ($this->getCheckoutFieldLayout($position)->getCustomFieldElements() as $layoutElement) {
				$handles[] = (string) $layoutElement->getField()->handle;
			}
		}

		/** @var array<int, GatewayInterface> $gateways */
		$gateways = Commerce::getInstance()?->getGateways()->getAllGateways() ?? [];

		foreach ($gateways as $gateway) {
			foreach ($this->getFieldLayout((string) $gateway->handle)->getCustomFieldElements() as $layoutElement) {
				$handles[] = (string) $layoutElement->getField()->handle;
			}
		}

		return array_values(array_unique($handles));
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
		return $this->storeLayout(self::CONFIG_KEY, $gatewayHandle, $layout);
	}

	public function saveCheckoutFieldLayout(string $position, FieldLayout $layout): bool
	{
		return $this->storeLayout(self::CHECKOUT_CONFIG_KEY, $position, $layout);
	}

	private function layoutAt(string $configKey, string $key): FieldLayout
	{
		$stored = $this->storedLayout($configKey, $key);

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

	private function storeLayout(string $configKey, string $key, FieldLayout $layout): bool
	{
		if (! $layout->validate()) {
			return false;
		}

		$layout->uid ??= StringHelper::UUID();
		$layout->type = Order::class;

		Craft::$app->getProjectConfig()->set($configKey . '.' . $key, [
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
	private function storedLayout(string $configKey, string $key): ?array
	{
		$stored = Craft::$app->getProjectConfig()->get($configKey . '.' . $key);

		if (! is_array($stored) || $stored === []) {
			return null;
		}

		/** @var array<string, array<string, mixed>> $stored */
		return $stored;
	}
}
