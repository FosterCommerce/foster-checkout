<?php

namespace fostercommerce\fostercheckout\conditions;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use fostercommerce\fostercheckout\FosterCheckout;

class OptionValueConditionRule extends BaseTextConditionRule
{
	#[\Override]
	public function getLabel(): string
	{
		return Craft::t(FosterCheckout::HANDLE, 'settings.lineItemOptions.optionValue');
	}

	/**
	 * Reads as a sentence on the rules table, so the whole rule is legible without opening it.
	 */
	public function describe(): string
	{
		$subject = Craft::t(FosterCheckout::HANDLE, 'settings.lineItemOptions.optionValue');
		$operator = $this->operatorLabel($this->operator);

		if ($this->value === '') {
			return "{$subject} {$operator}";
		}

		return "{$subject} {$operator} “{$this->value}”";
	}

	public function matches(string $optionValue): bool
	{
		return $this->matchValue($optionValue);
	}
}
