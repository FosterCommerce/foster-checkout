<?php

namespace fostercommerce\fostercheckout\conditions;

use craft\base\conditions\BaseCondition;

class LineItemOptionCondition extends BaseCondition
{
	/**
	 * Rules test the option as stored, so an earlier rewrite never hides an option from a later rule.
	 */
	public function matches(string $optionName, string $optionValue): bool
	{
		foreach ($this->getConditionRules() as $conditionRule) {
			$matched = match (true) {
				$conditionRule instanceof OptionNameConditionRule => $conditionRule->matches($optionName),
				$conditionRule instanceof OptionValueConditionRule => $conditionRule->matches($optionValue),
				default => true,
			};

			if (! $matched) {
				return false;
			}
		}

		return true;
	}

	#[\Override]
	protected function selectableConditionRules(): array
	{
		return [
			OptionNameConditionRule::class,
			OptionValueConditionRule::class,
		];
	}
}
