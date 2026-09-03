<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Model;
use craft\helpers\StringHelper;
use fostercommerce\fostercheckout\conditions\LineItemOptionCondition;

/**
 * One rewrite applied to a line item option whose name and value satisfy the condition.
 */
class LineItemOptionRule extends Model
{
	/**
	 * Left alone when null, so a rule can rewrite the value without touching the name.
	 */
	public ?string $setName = null;

	public ?string $setValue = null;

	/**
	 * Rules are addressed by uid so reordering cannot make an edit or a delete target its neighbour.
	 */
	public string $uid;

	private readonly LineItemOptionCondition $condition;

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct($config = [])
	{
		$condition = $config['condition'] ?? null;
		unset($config['condition']);

		$this->condition = $condition instanceof LineItemOptionCondition
			? $condition
			: $this->createCondition(is_array($condition) ? $condition : []);

		$postedUid = $config['uid'] ?? null;
		$config['uid'] = is_string($postedUid) && $postedUid !== '' ? $postedUid : StringHelper::UUID();

		parent::__construct($config);
	}

	public function getCondition(): LineItemOptionCondition
	{
		return $this->condition;
	}

	/**
	 * The condition is not a public attribute, so settings storage would otherwise drop it.
	 *
	 * @return array<string, string|callable>
	 */
	#[\Override]
	public function fields(): array
	{
		$fields = parent::fields();
		$fields['condition'] = fn (): array => $this->condition->getConfig();

		return $fields;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toConfig(): array
	{
		return [
			'uid' => $this->uid,
			'condition' => $this->condition->getConfig(),
			'setName' => $this->setName,
			'setValue' => $this->setValue,
		];
	}

	/**
	 * @param array<array-key, mixed> $config
	 */
	private function createCondition(array $config): LineItemOptionCondition
	{
		$condition = Craft::$app->getConditions()->createCondition(LineItemOptionCondition::class);

		// The builder emits a form of its own, which the edit form would otherwise nest
		$condition->mainTag = 'div';

		$rules = $config['conditionRules'] ?? null;

		if (is_array($rules)) {
			$condition->setConditionRules($rules);
		}

		return $condition;
	}
}
