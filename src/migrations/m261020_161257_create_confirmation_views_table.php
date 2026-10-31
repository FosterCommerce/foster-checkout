<?php

namespace fostercommerce\fostercheckout\migrations;

use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use craft\helpers\Db;
use fostercommerce\fostercheckout\db\Table;

/**
 * Creates the confirmation views table on existing installs.
 */
class m261020_161257_create_confirmation_views_table extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		// A database restored without its migration rows can already have the table, or the table without its key
		if (! $this->db->tableExists(Table::CONFIRMATION_VIEWS)) {
			$this->createTable(Table::CONFIRMATION_VIEWS, [
				'orderId' => $this->integer()->notNull(),
				'dateCreated' => $this->dateTime()->notNull(),
				'PRIMARY KEY([[orderId]])',
			]);
		}

		if (Db::findForeignKey(Table::CONFIRMATION_VIEWS, 'orderId') === null) {
			$this->addForeignKey(null, Table::CONFIRMATION_VIEWS, ['orderId'], CommerceTable::ORDERS, ['id'], 'CASCADE');
		}

		return true;
	}
}
