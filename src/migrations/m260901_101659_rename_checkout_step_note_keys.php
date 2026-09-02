<?php

namespace fostercommerce\fostercheckout\migrations;

use craft\db\Migration;
use fostercommerce\fostercheckout\records\Content;

/**
 * Renames the stored note keys so notes, field layout slots and the documented `step` values all
 * use one name per checkout step.
 */
class m260901_101659_rename_checkout_step_note_keys extends Migration
{
	/**
	 * @var array<string, string>
	 */
	private const array RENAMED_KEYS = [
		'address' => 'shippingAddress',
		'shipping' => 'shippingMethod',
		'order' => 'confirmation',
	];

	#[\Override]
	public function safeUp(): bool
	{
		/** @var Content[] $records */
		$records = Content::find()->all();

		foreach ($records as $record) {
			$content = $record->content;
			$notes = $content['notes'] ?? null;

			if (! is_array($notes)) {
				continue;
			}

			$renamed = false;

			foreach (self::RENAMED_KEYS as $oldKey => $newKey) {
				if (! array_key_exists($oldKey, $notes)) {
					continue;
				}

				// A note already under the new key was written after the rename, so it wins
				$notes[$newKey] ??= $notes[$oldKey];
				unset($notes[$oldKey]);
				$renamed = true;
			}

			if (! $renamed) {
				continue;
			}

			$content['notes'] = $notes;
			$record->content = $content;

			// Failing here would drop copy an admin wrote, so the migration stops instead
			if (! $record->save()) {
				return false;
			}
		}

		return true;
	}
}
