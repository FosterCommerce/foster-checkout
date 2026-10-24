<?php

namespace fostercommerce\fostercheckout\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use fostercommerce\fostercheckout\FosterCheckout;

/**
 * Removes the stored `enableEstimatedShipping` option, which logs a deprecation warning on every request.
 */
class m261024_024237_remove_estimated_shipping_setting extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$projectConfig = Craft::$app->getProjectConfig();
		$pluginPath = ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE;

		$schemaVersion = $projectConfig->get($pluginPath . '.schemaVersion', true);

		// Project config already has the option removed when another environment applied this migration first
		if (is_string($schemaVersion) && version_compare($schemaVersion, '1.6.0', '>=')) {
			return true;
		}

		$optionsPath = $pluginPath . '.settings.options';
		$options = ProjectConfigHelper::unpackAssociativeArray((array) ($projectConfig->get($optionsPath) ?? []));

		if (! array_key_exists('enableEstimatedShipping', $options)) {
			return true;
		}

		unset($options['enableEstimatedShipping']);
		$projectConfig->set($optionsPath, ProjectConfigHelper::packAssociativeArray($options));

		return true;
	}
}
