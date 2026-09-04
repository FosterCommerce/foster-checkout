<?php

namespace fostercommerce\fostercheckout\plugin;

use craft\web\twig\variables\CraftVariable;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;

trait Variables
{
	private function registerTwigVariable(): void
	{
		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			function (Event $event): void {
				/** @var CraftVariable $variable */
				$variable = $event->sender;

				$variable->set('fostercheckout', Checkout::class);
			}
		);
	}
}
