<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Model;
use craft\helpers\Json;
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
		$config['uid'] = is_string($postedUid) && $postedUid !== ''
			? $postedUid
			: $this->derivedUid($this->condition, $config);

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
	 * Craft gives a condition rule a fresh uid whenever the config omits one.
	 *
	 * @param array<array-key, mixed> $config
	 * @return array<array-key, mixed>
	 */
	private static function withoutUids(array $config): array
	{
		unset($config['uid']);

		foreach ($config as $key => $value) {
			if (is_array($value)) {
				$config[$key] = self::withoutUids($value);
			}
		}

		return $config;
	}

	/**
	 * A rule set in a config file carries no uid, and a fresh one each request would break the
	 * links that address it.
	 *
	 * @param array<array-key, mixed> $config
	 */
	private function derivedUid(LineItemOptionCondition $condition, array $config): string
	{
		$digest = md5(Json::encode([
			self::withoutUids($condition->getConfig()),
			$config['setName'] ?? null,
			$config['setValue'] ?? null,
		]));

		// Craft routes a uid on UUID_PATTERN, which wants a version 4 and a variant nibble
		return implode('-', [
			substr($digest, 0, 8),
			substr($digest, 8, 4),
			'4' . substr($digest, 13, 3),
			['8', '9', 'a', 'b'][hexdec($digest[16]) % 4] . substr($digest, 17, 3),
			substr($digest, 20, 12),
		]);
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
