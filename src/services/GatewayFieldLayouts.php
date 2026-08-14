<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\commerce\elements\Order;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use yii\base\Component;

/**
 * Field layouts service.
 *
 * Layouts are kept in project config, never in the `fieldlayouts` table, because Craft looks that
 * table up by element type and a stored row could come back as the order's own layout.
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
	 * Fields a gateway asks for, flattened for the storefront.
	 *
	 * Type and bounds come from the Craft field, which a layout cannot express.
	 *
	 * @return array<int, array{handle: string, label: string, instructions: ?string, required: bool, width: int, type: string, placeholder: ?string, maxLength: ?int, min: ?int, max: ?int}>
	 */
	public function getRenderableFields(string $gatewayHandle): array
	{
		$fields = [];

		foreach ($this->getFieldLayout($gatewayHandle)->getCustomFieldElements() as $customField) {
			$field = $customField->getField();

			$fields[] = [
				'handle' => (string) $field->handle,
				'label' => $customField->label ?? (string) $field->name,
				'instructions' => $customField->instructions,
				'required' => $customField->required,
				'width' => $customField->width,
				'type' => $field instanceof Number ? 'number' : 'text',
				'placeholder' => $field instanceof PlainText ? $field->placeholder : null,
				'maxLength' => $field instanceof PlainText ? $field->charLimit : null,
				'min' => $field instanceof Number ? (int) $field->min : null,
				'max' => $field instanceof Number && $field->max !== null ? (int) $field->max : null,
			];
		}

		return $fields;
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
