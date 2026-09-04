<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\Plugin as Commerce;
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
 * Copy is stored in the database rather than project config so it stays editable on production,
 * where admin changes are disallowed.
 */
class ContentController extends Controller
{
	// `customersOrderNotes` is absent: it names an order field handle, so it is developer config.
	/**
	 * @var list<string>
	 */
	private const array NOTE_KEYS = [
		'cart',
		'emptyCart',
		'login',
		'email',
		'shippingAddress',
		'shippingMethod',
		'billing',
		'payment',
		'confirmation',
		'globalCheckout',
		'subscribe',
		'deliveryDateLabel',
		'deliveryDateMessage',
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
			// _layouts/base.twig sets `requestedSite` itself, so one passed here is overwritten
			'contentSite' => $this->resolveSite(),
			'showSiteMenu' => $this->showSiteMenu(),
			'noteKeys' => self::NOTE_KEYS,
			'notes' => $plugin->getContent()->get('notes') ?? [],
			'footerLinks' => $plugin->getContent()->get('links.footerLinks') ?? [],
			'gateways' => $this->commerce()->getGateways()->getAllGateways(),
			'gatewayNotes' => $plugin->getContent()->get('notes.gateways') ?? [],
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
		$content = $plugin->getContent()->all();
		$storedNotes = is_array($content['notes'] ?? null) ? $content['notes'] : [];

		foreach (self::NOTE_KEYS as $noteKey) {
			$storedNotes[$noteKey] = (string) ($postedNotes[$noteKey] ?? '');
		}

		$postedGatewayNotes = is_array($postedNotes['gateways'] ?? null) ? $postedNotes['gateways'] : [];
		$gatewayNotes = is_array($storedNotes['gateways'] ?? null) ? $storedNotes['gateways'] : [];

		foreach ($this->gatewayHandles() as $gatewayHandle) {
			$gatewayNotes[$gatewayHandle] = (string) ($postedGatewayNotes[$gatewayHandle] ?? '');
		}

		$storedNotes['gateways'] = $gatewayNotes;

		$storedLinks = is_array($content['links'] ?? null) ? $content['links'] : [];
		$storedLinks['footerLinks'] = $this->postedFooterLinks();

		$content['notes'] = $storedNotes;
		$content['links'] = $storedLinks;

		if (! $plugin->getContent()->save($content)) {
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
	private function gatewayHandles(): array
	{
		$gatewayHandles = [];

		$gateways = $this->commerce()->getGateways()->getAllGateways();

		foreach ($gateways as $gateway) {
			if ($gateway instanceof GatewayInterface && is_string($gateway->handle)) {
				$gatewayHandles[] = $gateway->handle;
			}
		}

		return $gatewayHandles;
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
	 * The menu lists editable sites, so Non-translatable copy is shared, so the site menu has nothing to switch.
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

	/**
	 * Commerce is a hard requirement, so its instance is always there.
	 */
	private function commerce(): Commerce
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();

		return $commerce;
	}
}
