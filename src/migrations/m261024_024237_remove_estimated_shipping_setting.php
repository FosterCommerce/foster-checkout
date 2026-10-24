<?php

namespace fostercommerce\fostercheckout\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use fostercommerce\fostercheckout\FosterCheckout;

/**
 * Removes the stored `enableEstimatedShipping` option, which `OptionConfig` warns about on every request.
 */
class m261024_024237_remove_estimated_shipping_setting extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$projectConfig = Craft::$app->getProjectConfig();
		$pluginPath = ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE;

		$schemaVersion = $projectConfig->get($pluginPath . '.schemaVersion', true);

		// Another environment's run already removed the option from the YAML
		if (is_string($schemaVersion) && version_compare($schemaVersion, '1.6.0', '>=')) {
			return true;
		}

		$optionsPath = $pluginPath . '.settings.options';
		$options = ProjectConfigHelper::unpackAssociativeArray((array) ($projectConfig->get($optionsPath) ?? []));

		unset($options['enableEstimatedShipping']);
		$projectConfig->set($optionsPath, ProjectConfigHelper::packAssociativeArray($options));

		return true;
	}
}
