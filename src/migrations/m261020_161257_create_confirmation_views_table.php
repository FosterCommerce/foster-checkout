<?php

namespace fostercommerce\fostercheckout\migrations;

use craft\commerce\db\Table as CommerceTable;
use craft\db\Migration;
use fostercommerce\fostercheckout\db\Table;

/**
 * Creates the confirmation views table on existing installs.
 */
class m261020_161257_create_confirmation_views_table extends Migration
{
	#[\Override]
	public function safeUp(): bool
	{
		$this->createTable(Table::CONFIRMATION_VIEWS, [
			'orderId' => $this->integer()->notNull(),
			'dateCreated' => $this->dateTime()->notNull(),
			'PRIMARY KEY([[orderId]])',
		]);

		$this->addForeignKey(null, Table::CONFIRMATION_VIEWS, ['orderId'], CommerceTable::ORDERS, ['id'], 'CASCADE');

		return true;
	}
}
