<?php

namespace fostercommerce\fostercheckout\migrations;

use craft\db\Migration;
use fostercommerce\fostercheckout\db\Table;

/**
 * Carries existing installs forward to the schema in Install.php.
 */
class m260813_202505_create_content_table extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$this->createTable(Table::CONTENT, [
			'id' => $this->primaryKey(),
			'translationKey' => $this->string()->notNull(),
			'content' => $this->json(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
		]);

		$this->createIndex(null, Table::CONTENT, ['translationKey'], true);

		return true;
	}
}
