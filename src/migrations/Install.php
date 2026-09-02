<?php

namespace fostercommerce\fostercheckout\migrations;

use craft\db\Migration;
use fostercommerce\fostercheckout\db\Table;

/**
 * Cumulative install reflecting the full schema. Every schema change must also ship an
 * incremental `m*` migration so existing installs get the same change.
 *
 * A data migration that reads a site's existing config file must also be replayed in `safeUp()`,
 * since a fresh install never runs it.
 */
class Install extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$this->archiveTableIfExists(Table::CONTENT);

		// Checkout copy is admin-editable on production, so it cannot live in project config.
		$this->createTable(Table::CONTENT, [
			'id' => $this->primaryKey(),
			'translationKey' => $this->string()->notNull(),
			'content' => $this->json(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
		]);

		// One row per translation key, so a concurrent save collides instead of silently duplicating.
		$this->createIndex(null, Table::CONTENT, ['translationKey'], true);

		// Craft stamps every other migration as applied on a fresh install without running it, so a
		// site installing the plugin today would keep its config file and get none of its content.
		return (new m260813_234259_migrate_checkout_content())->safeUp()
			&& (new m260814_022939_convert_gateway_fields_to_layouts())->safeUp()
			&& (new m260901_101659_rename_checkout_step_note_keys())->safeUp();
	}

	#[\Override]
	public function safeDown(): bool
	{
		$this->dropTableIfExists(Table::CONTENT);

		return true;
	}
}
