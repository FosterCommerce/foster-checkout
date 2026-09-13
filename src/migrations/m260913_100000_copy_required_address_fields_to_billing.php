<?php

namespace fostercommerce\fostercheckout\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;
use fostercommerce\fostercheckout\FosterCheckout;

/**
 * Required address fields applied to the billing address too until 1.5.0 split the lists, so a
 * store's list is copied to the billing side to keep what it required.
 */
class m260913_100000_copy_required_address_fields_to_billing extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$projectConfig = Craft::$app->getProjectConfig();
		$pluginPath = ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE;

		$schemaVersion = $projectConfig->get($pluginPath . '.schemaVersion', true);

		// Project config already carries the copy when another environment applied this migration first
		if (is_string($schemaVersion) && version_compare($schemaVersion, '1.4.0', '>=')) {
			return true;
		}

		$requiredAddressFields = $projectConfig->get($pluginPath . '.settings.requiredAddressFields');

		if (is_array($requiredAddressFields) && $requiredAddressFields !== [] && $projectConfig->get($pluginPath . '.settings.requiredBillingAddressFields') === null) {
			$projectConfig->set($pluginPath . '.settings.requiredBillingAddressFields', $requiredAddressFields);
		}

		return true;
	}

	#[\Override]
	public function safeDown(): bool
	{
		echo "m260913_100000_copy_required_address_fields_to_billing cannot be reverted.\n";

		return false;
	}
}
