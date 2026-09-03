<?php

namespace fostercommerce\fostercheckout\conditions;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use fostercommerce\fostercheckout\FosterCheckout;

class OptionNameConditionRule extends BaseTextConditionRule
{
	#[\Override]
	public function getLabel(): string
	{
		return Craft::t(FosterCheckout::HANDLE, 'settings.lineItemOptions.optionName');
	}

	public function matches(string $optionName): bool
	{
		return $this->matchValue($optionName);
	}
}
