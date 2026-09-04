<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\records\Content as ContentRecord;
use yii\base\Component;

/**
 * Checkout copy that store admins edit in the control panel.
 *
 * Held as a JSON blob per translation key rather than a row per key, so adding a key needs no migration.
 */
class Content extends Component
{
	/**
	 * @var array<string, array<string, mixed>>
	 */
	private array $content = [];

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		$translationKey = $this->translationKey();

		if (! isset($this->content[$translationKey])) {
			$storedContent = $this->record()->content;

			$this->content[$translationKey] = $storedContent ?? [];
		}

		return $this->content[$translationKey];
	}

	/**
	 * Dotted path, such as `notes.cart`.
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
		// Craft encodes values bound to a json column, so assigning the array avoids double-encoding.
		$record->content = $content;

		if (! $record->save()) {
			return false;
		}

		$this->content[$this->translationKey()] = $content;

		return true;
	}

	/**
	 * Mirrors `ElementHelper::translationKey()`, which content cannot call directly without an element.
	 */
	public function translationKey(): string
	{
		$site = Craft::$app->getSites()->getCurrentSite();

		return match ($this->settings()->contentTranslationMethod) {
			Field::TRANSLATION_METHOD_NONE => '1',
			Field::TRANSLATION_METHOD_LANGUAGE => $site->language,
			// Only none, site and language are offered in the CP, so anything else keys by site
			default => (string) $site->id,
		};
	}

	private function record(): ContentRecord
	{
		$translationKey = $this->translationKey();

		$record = ContentRecord::find()
			->where([
				'translationKey' => $translationKey,
			])
			->one();

		return $record instanceof ContentRecord ? $record : new ContentRecord([
			'translationKey' => $translationKey,
		]);
	}

	private function settings(): Settings
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		/** @var Settings $settings */
		$settings = $plugin->getSettings();

		return $settings;
	}
}
