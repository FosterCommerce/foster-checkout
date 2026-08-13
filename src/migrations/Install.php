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
		// One row: the plugin supports a single store per install.
		$this->createTable(Table::CONTENT, [
			'id' => $this->primaryKey(),
			'content' => $this->json(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
		]);

		return true;
	}

	#[\Override]
	public function safeDown(): bool
	{
		$this->dropTableIfExists(Table::CONTENT);

		return true;
	}
}
