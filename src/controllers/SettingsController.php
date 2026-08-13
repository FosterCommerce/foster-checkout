<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\web\Controller;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\Settings;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Renders the plugin's settings pages and handles saves.
 *
 * Uses its own CP routes rather than `plugins/save-plugin-settings` so the form escapes the
 * `_layouts/cp` delta-tracking wrapper, which silently strips the POST body.
 */
class SettingsController extends Controller
{
	public function actionAppearance(): Response
	{
		return $this->renderSection('appearance');
	}

	public function actionFeatures(): Response
	{
		return $this->renderSection('features');
	}

	public function actionGeneral(): Response
	{
		return $this->renderSection('general');
	}

	/**
	 * @throws ForbiddenHttpException
	 */
	public function actionSaveSettings(): ?Response
	{
		$this->requirePostRequest();

		$postedSection = $this->request->getRequiredBodyParam('section');
		$section = is_string($postedSection) ? $postedSection : '';

		$this->requirePermission(FosterCheckout::settingsPermission($section));

		// Settings persist to project config, which is read-only when admin changes are
		// disabled; without this guard the save surfaces a raw NotSupportedException 500.
		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		// The form disables overridden fields, but a disabled input is not a control: without this
		// the save would write project config that the config file then hides on every request.
		$configKey = FosterCheckout::settingsConfigKey($section);

		if ($configKey !== null && in_array($configKey, $plugin->getOverriddenSettings(), true)) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.settingsOverridden'));
		}

		$postedSettings = $this->request->getBodyParam('settings', []);

		if (! is_array($postedSettings)) {
			$postedSettings = [];
		}

		/** @var Settings $settings */
		$settings = $plugin->getSettings();
		$settings->setAttributes($postedSettings, false);

		// savePluginSettings() persists only the keys it is handed and replaces the whole
		// settings node, so a single section must be saved alongside every other one.
		if (! Craft::$app->getPlugins()->savePluginSettings($plugin, $settings->toArray())) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.saveFailed'));

			return null;
		}

		$this->setSuccessFlash(Craft::t('app', 'Settings saved.'));

		return $this->redirectToPostedUrl();
	}

	private function renderSection(string $section): Response
	{
		$this->requirePermission(FosterCheckout::settingsPermission($section));

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		/** @var Settings $settings */
		$settings = $plugin->getSettings();

		return $this->renderTemplate("foster-checkout/settings/{$section}/index", [
			'plugin' => $plugin,
			'settings' => $settings,
			'overriddenSettings' => $plugin->getOverriddenSettings(),
		]);
	}
}
