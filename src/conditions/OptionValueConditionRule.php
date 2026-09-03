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

	public function matches(string $optionValue): bool
	{
		return $this->matchValue($optionValue);
	}
}
