<?php

namespace fostercommerce\fostercheckout\records;

use craft\db\ActiveRecord;
use fostercommerce\fostercheckout\db\Table;

/**
 * @property int $id
 * @property string $translationKey
 * @property array<string, mixed>|null $content
 * @property string $dateCreated
 * @property string $dateUpdated
 * @property string $uid
 */
class Content extends ActiveRecord
{
	#[\Override]
	public static function tableName(): string
	{
		return Table::CONTENT;
	}
}
