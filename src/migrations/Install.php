<?php

namespace fostercommerce\fostercheckout\migrations;

use craft\db\Migration;
use fostercommerce\fostercheckout\db\Table;

/**
 * Cumulative install reflecting the full schema. Every schema change must also ship an
 * incremental `m*` migration to carry existing installs forward.
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

		return true;
	}

	#[\Override]
	public function safeDown(): bool
	{
		$this->dropTableIfExists(Table::CONTENT);

		return true;
	}
}
