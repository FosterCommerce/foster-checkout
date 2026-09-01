<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\models\FieldLayout;
use craft\services\ProjectConfig;
use craft\web\Controller;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\services\GatewayFieldLayouts;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
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

	public function actionProducts(): Response
	{
		return $this->renderSection('products');
	}

	public function actionGateways(): Response
	{
		return $this->renderSection('gateways');
	}

	/**
	 * @throws NotFoundHttpException
	 */
	public function actionEditGateway(string $gatewayHandle): Response
	{
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$gateway = $this->gateway($gatewayHandle);

		/** @var Settings $settings */
		$settings = $plugin->getSettings();

		return $this->renderTemplate('foster-checkout/settings/gateways/_edit', [
			'gateway' => $gateway,
			'settings' => $settings,
			'gatewayConfig' => $settings->paymentGateways[$gatewayHandle] ?? null,
			'fieldLayout' => $plugin->gatewayFieldLayouts->getFieldLayout($gatewayHandle),
			'overriddenSettings' => $plugin->getOverriddenSettings(),
		]);
	}

	/**
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionSaveGateway(): ?Response
	{
		$this->requirePostRequest();
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		$postedHandle = $this->request->getRequiredBodyParam('gatewayHandle');
		$gatewayHandle = is_string($postedHandle) ? $postedHandle : '';
		$this->gateway($gatewayHandle);

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$layout = Craft::$app->getFields()->assembleLayoutFromPost();
		$layout->type = Order::class;

		$unstorable = $this->unstorableFieldHandles($layout, $plugin->gatewayFieldLayouts->orderFieldHandles());

		// An order only saves values for fields in its own layout, so anything else would render,
		// accept what the customer types, and then be discarded without an error.
		if ($unstorable !== []) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.gateways.unstorableFields', [
				'fields' => implode(', ', $unstorable),
			]));

			Craft::$app->getUrlManager()->setRouteParams([
				'fieldLayout' => $layout,
			]);

			return null;
		}

		$unsupported = $this->unsupportedFields($layout, $plugin->gatewayFieldLayouts);

		// No input exists for the type, so it's ignored in the storefront form.
		if ($unsupported !== []) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.gateways.unsupportedFields', [
				'fields' => implode(', ', $unsupported),
			]));

			Craft::$app->getUrlManager()->setRouteParams([
				'fieldLayout' => $layout,
			]);

			return null;
		}

		if (! $plugin->gatewayFieldLayouts->saveFieldLayout($gatewayHandle, $layout)) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.saveFailed'));

			Craft::$app->getUrlManager()->setRouteParams([
				'fieldLayout' => $layout,
			]);

			return null;
		}

		$this->saveGatewayOptions($gatewayHandle);
		$this->setSuccessFlash(Craft::t('app', 'Settings saved.'));

		return $this->redirectToPostedUrl();
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

		// savePluginSettings() persists only the keys it is handed and replaces the whole settings
		// node, so the posted section is merged over what is already stored. The stored config is
		// merged rather than the settings model, which cannot round-trip a gateway note defined as
		// a PHP closure.
		$storedSettings = ProjectConfigHelper::unpackAssociativeArrays(
			(array) (Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE . '.settings') ?? [])
		);

		$allSettings = array_merge($storedSettings, $this->normalizeTables($postedSettings, $storedSettings));

		if (! Craft::$app->getPlugins()->savePluginSettings($plugin, $allSettings)) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.saveFailed'));

			// savePluginSettings() leaves the rejected values and their errors on the model, so
			// handing it back lets the form redisplay both.
			Craft::$app->getUrlManager()->setRouteParams([
				'settings' => $plugin->getSettings(),
			]);

			return null;
		}

		$this->setSuccessFlash(Craft::t('app', 'Settings saved.'));

		return $this->redirectToPostedUrl();
	}

	/**
	 * @throws NotFoundHttpException
	 */
	private function gateway(string $gatewayHandle): GatewayInterface
	{
		$gateway = Commerce::getInstance()?->getGateways()->getGatewayByHandle($gatewayHandle);

		if (! $gateway instanceof GatewayInterface) {
			throw new NotFoundHttpException("No payment gateway with handle {$gatewayHandle}.");
		}

		return $gateway;
	}

	/**
	 * @param array<int, string> $orderFieldHandles
	 * @return array<int, string>
	 */
	private function unstorableFieldHandles(FieldLayout $layout, array $orderFieldHandles): array
	{
		$unstorable = [];

		foreach ($layout->getCustomFieldElements() as $customField) {
			$handle = $customField->getField()->handle;

			if (is_string($handle) && ! in_array($handle, $orderFieldHandles, true)) {
				$unstorable[] = $handle;
			}
		}

		return $unstorable;
	}

	/**
	 * @return array<int, string>
	 */
	private function unsupportedFields(FieldLayout $layout, GatewayFieldLayouts $gatewayFieldLayouts): array
	{
		$unsupported = [];

		foreach ($layout->getCustomFieldElements() as $customField) {
			$field = $customField->getField();

			if ($gatewayFieldLayouts->fieldInputType($field) === null) {
				$unsupported[] = sprintf('%s (%s)', (string) $field->name, $field::displayName());
			}
		}

		return $unsupported;
	}

	/**
	 * Layout columns and payment form params sit alongside the field layout rather than in it.
	 */
	private function saveGatewayOptions(string $gatewayHandle): void
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		// The field layout lives outside plugin settings, so it stays editable. These two do not,
		// and the config file overrides them.
		if (in_array('paymentGateways', $plugin->getOverriddenSettings(), true)) {
			return;
		}

		$storedSettings = ProjectConfigHelper::unpackAssociativeArrays(
			(array) (Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE . '.settings') ?? [])
		);

		$storedGateways = is_array($storedSettings['paymentGateways'] ?? null) ? $storedSettings['paymentGateways'] : [];
		$gateway = is_array($storedGateways[$gatewayHandle] ?? null) ? $storedGateways[$gatewayHandle] : [];

		$postedColumns = $this->request->getBodyParam('columns', '');
		$columns = is_scalar($postedColumns) ? trim((string) $postedColumns) : '';
		$gateway['columns'] = $columns === '' ? null : (int) $columns;
		$gateway['params'] = $this->normalizeGatewayParams((array) $this->request->getBodyParam('params', []));

		$storedGateways[$gatewayHandle] = $gateway;
		$storedSettings['paymentGateways'] = $storedGateways;

		Craft::$app->getPlugins()->savePluginSettings($plugin, $storedSettings);
	}

	/**
	 * @return array<int, string>
	 */
	private function productTypeHandles(): array
	{
		$commerce = Commerce::getInstance();

		if (! $commerce instanceof Commerce) {
			return [];
		}

		$productTypeHandles = [];

		foreach ($commerce->getProductTypes()->getAllProductTypes() as $productType) {
			if (is_string($productType->handle)) {
				$productTypeHandles[] = $productType->handle;
			}
		}

		return $productTypeHandles;
	}

	/**
	 * Each gateway's stored config is kept and only the posted keys replaced, so a `note` defined
	 * as a PHP closure survives a settings save.
	 *
	 * @param array<array-key, mixed> $postedGateways
	 * @param array<array-key, mixed> $storedGateways
	 * @return array<string, mixed>
	 */
	private function normalizeGateways(array $postedGateways, array $storedGateways): array
	{
		$gateways = [];

		foreach ($postedGateways as $gatewayHandle => $postedGateway) {
			if (! is_string($gatewayHandle)) {
				continue;
			}

			if (! is_array($postedGateway)) {
				continue;
			}

			$gateway = is_array($storedGateways[$gatewayHandle] ?? null) ? $storedGateways[$gatewayHandle] : [];
			$columns = $this->trimmedString($postedGateway, 'columns');

			$gateway['columns'] = $columns === '' ? null : (int) $columns;
			$gateway['fields'] = $this->normalizeGatewayFields((array) ($postedGateway['fields'] ?? []));
			$gateway['params'] = $this->normalizeGatewayParams((array) ($postedGateway['params'] ?? []));

			$gateways[$gatewayHandle] = $gateway;
		}

		return $gateways;
	}

	/**
	 * @param array<array-key, mixed> $postedFields
	 * @return array<string, array<string, mixed>>
	 */
	private function normalizeGatewayFields(array $postedFields): array
	{
		$fields = [];

		foreach ($postedFields as $postedField) {
			if (! is_array($postedField)) {
				continue;
			}

			$fieldHandle = $this->trimmedString($postedField, 'handle');

			if ($fieldHandle === '') {
				continue;
			}

			$fieldType = $this->trimmedString($postedField, 'type');

			$fields[$fieldHandle] = [
				'type' => $fieldType === '' ? 'text' : $fieldType,
				'label' => $this->trimmedString($postedField, 'label'),
				'placeholder' => $this->trimmedString($postedField, 'placeholder'),
				'required' => (bool) ($postedField['required'] ?? false),
				// The models use `false`, not null or 0, to mean "no bound".
				'minLength' => $this->boundOrFalse($postedField, 'minLength'),
				'maxLength' => $this->boundOrFalse($postedField, 'maxLength'),
				'min' => $this->boundOrFalse($postedField, 'min'),
				'max' => $this->boundOrFalse($postedField, 'max'),
				'columns' => $this->trimmedString($postedField, 'columns') === '' ? null : (int) $this->trimmedString($postedField, 'columns'),
			];
		}

		return $fields;
	}

	/**
	 * @param array<array-key, mixed> $postedParams
	 * @return array<string, string>
	 */
	private function normalizeGatewayParams(array $postedParams): array
	{
		$params = [];

		foreach ($postedParams as $postedParam) {
			if (! is_array($postedParam)) {
				continue;
			}

			$key = $this->trimmedString($postedParam, 'key');

			if ($key !== '') {
				$params[$key] = $this->trimmedString($postedParam, 'value');
			}
		}

		return $params;
	}

	/**
	 * @param array<array-key, mixed> $row
	 */
	private function boundOrFalse(array $row, string $key): int|false
	{
		$bound = $this->trimmedString($row, $key);

		return $bound === '' ? false : (int) $bound;
	}

	/**
	 * @param array<array-key, mixed> $row
	 */
	private function trimmedString(array $row, string $key): string
	{
		$value = $row[$key] ?? '';

		return is_string($value) ? trim($value) : '';
	}

	/**
	 * Editable tables and checkbox groups post rows, but the settings model holds a map keyed by
	 * product type handle and plain lists of codes and handles.
	 *
	 * @param array<array-key, mixed> $postedSettings
	 * @param array<array-key, mixed> $storedSettings
	 * @return array<array-key, mixed>
	 */
	private function normalizeTables(array $postedSettings, array $storedSettings): array
	{
		if (isset($postedSettings['paymentGateways'])) {
			$postedSettings['paymentGateways'] = $this->normalizeGateways(
				(array) $postedSettings['paymentGateways'],
				is_array($storedSettings['paymentGateways'] ?? null) ? $storedSettings['paymentGateways'] : []
			);
		}

		if (isset($postedSettings['products'])) {
			$products = [];

			foreach ((array) $postedSettings['products'] as $productRow) {
				if (! is_array($productRow)) {
					continue;
				}

				$productTypeHandle = $this->trimmedString($productRow, 'productTypeHandle');

				if ($productTypeHandle === '') {
					continue;
				}

				$productImageHandle = $this->trimmedString($productRow, 'productImageHandle');
				$variantImageHandle = $this->trimmedString($productRow, 'variantImageHandle');

				$products[$productTypeHandle] = [
					'productImageHandle' => $productImageHandle === '' ? null : $productImageHandle,
					'variantImageHandle' => $variantImageHandle === '' ? null : $variantImageHandle,
				];
			}

			$postedSettings['products'] = $products;
		}

		if (isset($postedSettings['priorityCountries'])) {
			$priorityCountries = [];

			foreach ((array) $postedSettings['priorityCountries'] as $countryRow) {
				$countryCode = is_array($countryRow) ? $this->trimmedString($countryRow, 'code') : '';

				if ($countryCode !== '') {
					$priorityCountries[] = $countryCode;
				}
			}

			$postedSettings['priorityCountries'] = $priorityCountries;
		}

		if (isset($postedSettings['zeroValueGatewayHandles'])) {
			// Craft's checkbox group posts an empty entry so an all-unchecked group still submits.
			$postedSettings['zeroValueGatewayHandles'] = array_values(array_filter(
				(array) $postedSettings['zeroValueGatewayHandles'],
				static fn ($gatewayHandle): bool => is_string($gatewayHandle) && $gatewayHandle !== ''
			));
		}

		return $postedSettings;
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
			'productTypeHandles' => $this->productTypeHandles(),
			'gateways' => Commerce::getInstance()?->getGateways()->getAllGateways() ?? [],
		]);
	}
}
