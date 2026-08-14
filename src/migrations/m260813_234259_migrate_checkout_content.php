<?php

namespace fostercommerce\fostercheckout\migrations;

use Craft;
use craft\base\ElementInterface;
use craft\db\Migration;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use fostercommerce\fostercheckout\FosterCheckout;

/**
 * Copies checkout copy out of the entry and global set fields each site pointed at, into the
 * plugin's own content storage, so admins can edit it on production.
 *
 * Reads the config file rather than the settings model, because the same release retires the
 * `notes` and `links` models this would otherwise depend on.
 */
class m260813_234259_migrate_checkout_content extends Migration
{
	/**
	 * `customersOrderNotes` is excluded: it names an order field handle, so it is developer
	 * config rather than copy.
	 *
	 * @var array<int, string>
	 */
	private const array NOTE_KEYS = [
		'cart',
		'emptyCart',
		'login',
		'email',
		'address',
		'shipping',
		'billing',
		'payment',
		'order',
		'globalCheckout',
		'mistakeHeading',
		'mistakeText',
	];

	#[\Override]
	public function safeUp(): bool
	{
		$fileConfig = Craft::$app->getConfig()->getConfigFromFile(FosterCheckout::HANDLE);

		if (! is_array($fileConfig)) {
			return true;
		}

		$notesConfig = is_array($fileConfig['notes'] ?? null) ? $fileConfig['notes'] : [];
		$linksConfig = is_array($fileConfig['links'] ?? null) ? $fileConfig['links'] : [];

		$plugin = FosterCheckout::getInstance();

		if (! $plugin instanceof FosterCheckout) {
			return true;
		}

		$sitesService = Craft::$app->getSites();
		$originalSite = $sitesService->getCurrentSite();
		$sites = $sitesService->getAllSites();

		foreach ($sites as $site) {
			$sitesService->setCurrentSite($site);

			$content = $plugin->content->all();
			$notes = is_array($content['notes'] ?? null) ? $content['notes'] : [];
			$links = is_array($content['links'] ?? null) ? $content['links'] : [];

			foreach (self::NOTE_KEYS as $noteKey) {
				// Anything already entered in the control panel wins, so re-running is safe.
				if (isset($notes[$noteKey])) {
					continue;
				}

				$value = $this->resolveFieldValue($notesConfig[$noteKey] ?? null);

				if (is_string($value) && trim($value) !== '') {
					$notes[$noteKey] = $value;
				}
			}

			if (! isset($links['footerLinks'])) {
				$footerLinks = $this->resolveFooterLinks($linksConfig['footerLinks'] ?? null);

				if ($footerLinks !== []) {
					$links['footerLinks'] = $footerLinks;
				}
			}

			$content['notes'] = $notes;
			$content['links'] = $links;

			$plugin->content->save($content);
		}

		$sitesService->setCurrentSite($originalSite);

		return true;
	}

	/**
	 * @param mixed $valueConfig an `elementHandle`/`fieldHandle` pair from the config file
	 */
	private function resolveFieldValue(mixed $valueConfig): ?string
	{
		$fieldValue = $this->fieldValue($valueConfig);

		if ($fieldValue === null || is_array($fieldValue)) {
			return null;
		}

		// Rich text fields hand back a Markup object rather than a string.
		return is_scalar($fieldValue) || $fieldValue instanceof \Stringable ? (string) $fieldValue : null;
	}

	/**
	 * @return array<int, array{text: string, url: string}>
	 */
	private function resolveFooterLinks(mixed $valueConfig): array
	{
		$rows = $this->fieldValue($valueConfig);

		if (! is_array($rows)) {
			return [];
		}

		$footerLinks = [];

		foreach ($rows as $row) {
			$text = is_array($row) ? trim((string) ($row['text'] ?? '')) : '';
			$url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';

			if ($text !== '' && $url !== '') {
				$footerLinks[] = [
					'text' => $text,
					'url' => $url,
				];
			}
		}

		return $footerLinks;
	}

	private function fieldValue(mixed $valueConfig): mixed
	{
		if (! is_array($valueConfig)) {
			return null;
		}

		$elementHandle = trim((string) ($valueConfig['elementHandle'] ?? ''));
		$fieldHandle = trim((string) ($valueConfig['fieldHandle'] ?? ''));

		if ($elementHandle === '' || $fieldHandle === '') {
			return null;
		}

		$element = GlobalSet::find()->handle($elementHandle)->one()
			?? Entry::find()->section($elementHandle)->one();

		if (! $element instanceof ElementInterface || ! $element->getFieldLayout()?->getFieldByHandle($fieldHandle) instanceof \craft\base\FieldInterface) {
			return null;
		}

		return $element->getFieldValue($fieldHandle);
	}
}
