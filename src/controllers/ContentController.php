<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\models\Site;
use craft\web\Controller;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\Settings;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Renders the checkout copy screen and handles saves.
 *
 * Copy lives in the database rather than project config so it stays editable on production,
 * where admin changes are disallowed.
 */
class ContentController extends Controller
{
	/**
	 * Note keys the screen exposes, each mapped to the option that must be enabled for the note
	 * to appear on the storefront. A null option means the note always applies.
	 *
	 * `customersOrderNotes` is deliberately absent: it names an order field handle, so it is
	 * developer config rather than copy.
	 *
	 * @var array<string, ?string>
	 */
	private const array NOTE_KEYS = [
		'cart' => null,
		'emptyCart' => null,
		'login' => null,
		'email' => null,
		'address' => null,
		'shipping' => null,
		'billing' => null,
		'payment' => null,
		'order' => null,
		'globalCheckout' => null,
		'mistakeHeading' => 'enableMadeAMistake',
		'mistakeText' => 'enableMadeAMistake',
	];

	/**
	 * @throws BadRequestHttpException
	 * @throws ForbiddenHttpException
	 */
	public function actionEdit(): Response
	{
		$this->requirePermission(FosterCheckout::PERMISSION_VIEW_CONTENT);

		$plugin = $this->plugin();

		return $this->renderTemplate('foster-checkout/content/index', [
			// Not `requestedSite`: Craft registers that as a CP Twig global, and it resolves to
			// null for users with no editable sites, which would shadow whatever is passed here.
			'contentSite' => $this->resolveSite(),
			'showSiteMenu' => $this->showSiteMenu(),
			'noteKeys' => $this->availableNoteKeys(),
			'notes' => $plugin->content->get('notes') ?? [],
			'footerLinks' => $plugin->content->get('links.footerLinks') ?? [],
			'readOnly' => ! Craft::$app->getUser()->checkPermission(FosterCheckout::PERMISSION_EDIT_CONTENT),
		]);
	}

	/**
	 * @throws BadRequestHttpException
	 * @throws ForbiddenHttpException
	 */
	public function actionSave(): ?Response
	{
		$this->requirePostRequest();
		$this->requirePermission(FosterCheckout::PERMISSION_EDIT_CONTENT);

		$this->resolveSite();
		$plugin = $this->plugin();

		$postedNotes = $this->request->getBodyParam('notes', []);

		if (! is_array($postedNotes)) {
			$postedNotes = [];
		}

		// Merge rather than replace: the blob also holds links, and notes whose feature is
		// switched off are not on the form but must keep their stored copy.
		$content = $plugin->content->all();
		$storedNotes = is_array($content['notes'] ?? null) ? $content['notes'] : [];

		foreach ($this->availableNoteKeys() as $noteKey) {
			$storedNotes[$noteKey] = (string) ($postedNotes[$noteKey] ?? '');
		}

		$storedLinks = is_array($content['links'] ?? null) ? $content['links'] : [];
		$storedLinks['footerLinks'] = $this->postedFooterLinks();

		$content['notes'] = $storedNotes;
		$content['links'] = $storedLinks;

		if (! $plugin->content->save($content)) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'content.saveFailed'));

			return null;
		}

		$this->setSuccessFlash(Craft::t(FosterCheckout::HANDLE, 'content.saved'));

		return $this->redirectToPostedUrl();
	}

	/**
	 * Rows missing either column are dropped, so the storefront never renders a link with no
	 * destination or no label.
	 *
	 * @return array<int, array{text: string, url: string}>
	 */
	private function postedFooterLinks(): array
	{
		$postedRows = $this->request->getBodyParam('links.footerLinks', []);

		if (! is_array($postedRows)) {
			return [];
		}

		$footerLinks = [];

		foreach ($postedRows as $postedRow) {
			$text = is_array($postedRow) ? trim((string) ($postedRow['text'] ?? '')) : '';
			$url = is_array($postedRow) ? trim((string) ($postedRow['url'] ?? '')) : '';

			if ($text !== '' && $url !== '') {
				$footerLinks[] = [
					'text' => $text,
					'url' => $url,
				];
			}
		}

		return $footerLinks;
	}

	/**
	 * @return array<int, string>
	 */
	private function availableNoteKeys(): array
	{
		/** @var Settings $settings */
		$settings = $this->plugin()->getSettings();

		$available = [];

		foreach (self::NOTE_KEYS as $noteKey => $requiredOption) {
			if ($requiredOption === null || $settings->options->{$requiredOption}) {
				$available[] = $noteKey;
			}
		}

		return $available;
	}

	/**
	 * Resolves the site being edited and makes it current, so the content service reads and
	 * writes the matching translation key.
	 *
	 * @throws BadRequestHttpException
	 * @throws ForbiddenHttpException
	 */
	private function resolveSite(): Site
	{
		$sitesService = Craft::$app->getSites();
		$siteHandle = $this->request->getParam('site');

		if (! is_string($siteHandle)) {
			return $sitesService->getCurrentSite();
		}

		$site = $sitesService->getSiteByHandle($siteHandle);

		if (! $site instanceof Site) {
			throw new BadRequestHttpException("Invalid site handle: {$siteHandle}");
		}

		if (! in_array($site->id, $sitesService->getEditableSiteIds(), true)) {
			throw new ForbiddenHttpException('User not permitted to edit content for this site.');
		}

		$sitesService->setCurrentSite($site);

		return $site;
	}

	/**
	 * Copy is shared across sites when it is not translatable, so switching sites would be a no-op.
	 * The menu lists editable sites, so it is also pointless for a user who has none.
	 */
	private function showSiteMenu(): bool
	{
		/** @var Settings $settings */
		$settings = $this->plugin()->getSettings();

		return Craft::$app->getIsMultiSite()
			&& $settings->contentTranslationMethod !== 'none'
			&& Craft::$app->getSites()->getEditableSiteIds() !== [];
	}

	private function plugin(): FosterCheckout
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin;
	}
}
