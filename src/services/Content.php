<?php

namespace fostercommerce\fostercheckout\services;

use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use fostercommerce\fostercheckout\records\Content as ContentRecord;
use yii\base\Component;

/**
 * Checkout copy that store admins edit in the control panel.
 *
 * Held as a single JSON row rather than a row per key, so adding a key needs no migration.
 */
class Content extends Component
{
	/**
	 * @var array<string, mixed>|null
	 */
	private ?array $content = null;

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		if ($this->content !== null) {
			return $this->content;
		}

		$storedContent = $this->record()->content;

		if ($storedContent === null) {
			return $this->content = [];
		}

		/** @var array<string, mixed> $decoded */
		$decoded = Json::decode($storedContent);

		return $this->content = $decoded;
	}

	/**
	 * Reads a dotted path, so `notes.cart` reaches into the stored structure.
	 */
	public function get(string $path): mixed
	{
		return ArrayHelper::getValue($this->all(), $path);
	}

	/**
	 * @param array<string, mixed> $content
	 */
	public function save(array $content): bool
	{
		$record = $this->record();
		$record->content = Json::encode($content);

		if (! $record->save()) {
			return false;
		}

		$this->content = $content;

		return true;
	}

	private function record(): ContentRecord
	{
		$record = ContentRecord::find()->one();

		return $record instanceof ContentRecord ? $record : new ContentRecord();
	}
}
