<?php

namespace fostercommerce\fostercheckout\controllers;

use Craft;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\services\ProjectConfig;
use craft\web\Controller;
use fostercommerce\fostercheckout\base\AddressLookupInterface;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\AddressLookupConfig;
use fostercommerce\fostercheckout\models\LineItemOptionRule;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use Throwable;
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
			'fieldLayout' => $plugin->getCheckoutFieldLayouts()->getFieldLayout($gatewayHandle),
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

		$unstorable = $this->unstorableFieldHandles($layout, $plugin->getCheckoutFieldLayouts()->orderFieldHandles());

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

		$unsupported = $this->unsupportedFields($layout, $plugin->getCheckoutFieldLayouts());

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

		if (! $plugin->getCheckoutFieldLayouts()->saveFieldLayout($gatewayHandle, $layout)) {
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

	public function actionFields(): Response
	{
		return $this->renderSection('fields');
	}

	/**
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionEditField(string $position): Response
	{
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		if (! in_array($position, CheckoutFieldLayouts::CHECKOUT_POSITIONS, true)) {
			throw new NotFoundHttpException();
		}

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $this->renderTemplate('foster-checkout/settings/fields/_edit', [
			'position' => $position,
			'fieldLayout' => $plugin->getCheckoutFieldLayouts()->getCheckoutFieldLayout($position),
		]);
	}

	/**
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionSaveField(): ?Response
	{
		$this->requirePostRequest();
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		$postedPosition = $this->request->getRequiredBodyParam('position');
		$position = is_string($postedPosition) ? $postedPosition : '';

		if (! in_array($position, CheckoutFieldLayouts::CHECKOUT_POSITIONS, true)) {
			throw new NotFoundHttpException();
		}

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$layout = Craft::$app->getFields()->assembleLayoutFromPost();
		$layout->type = Order::class;

		$unstorable = $this->unstorableFieldHandles($layout, $plugin->getCheckoutFieldLayouts()->orderFieldHandles());

		// An order only saves values for fields in its own layout, so anything else would render,
		// accept what the customer types, and then be discarded without an error.
		if ($unstorable !== []) {
			return $this->fieldLayoutFailure('settings.gateways.unstorableFields', $unstorable, $layout);
		}

		$unsupported = $this->unsupportedFields($layout, $plugin->getCheckoutFieldLayouts());

		// No input exists for the type, so it's ignored in the storefront form.
		if ($unsupported !== []) {
			return $this->fieldLayoutFailure('settings.gateways.unsupportedFields', $unsupported, $layout);
		}

		$claimed = array_intersect(
			$this->layoutFieldHandles($layout),
			$plugin->getCheckoutFieldLayouts()->claimedFieldHandles($position)
		);

		if ($claimed !== []) {
			return $this->fieldLayoutFailure('settings.fields.claimedFields', array_values($claimed), $layout);
		}

		if (! $plugin->getCheckoutFieldLayouts()->saveCheckoutFieldLayout($position, $layout)) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.saveFailed'));

			Craft::$app->getUrlManager()->setRouteParams([
				'fieldLayout' => $layout,
			]);

			return null;
		}

		$this->setSuccessFlash(Craft::t('app', 'Settings saved.'));

		return $this->redirectToPostedUrl();
	}

	public function actionTestAddressLookup(): Response
	{
		$this->requirePostRequest();
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		// Test the key on screen, which is the one the admin just pasted
		$posted = $this->request->getBodyParam('settings');
		$lookup = is_array($posted) && is_array($posted['addressLookup'] ?? null) ? $posted['addressLookup'] : [];
		$config = new AddressLookupConfig($lookup);
		$provider = $plugin->getAddressLookup()->providerFor($config);

		if (! $provider instanceof AddressLookupInterface) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.features.addressLookupTestOff'));

			return $this->redirect('foster-checkout/settings/features');
		}

		try {
			$provider->suggest('1 High Street', 'GB', null, StringHelper::UUID());
			$this->setSuccessFlash(Craft::t(FosterCheckout::HANDLE, 'settings.features.addressLookupTestPassed'));
		} catch (Throwable $throwable) {
			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.features.addressLookupTestFailed', [
				'message' => $throwable->getMessage(),
			]));
		}

		return $this->redirect('foster-checkout/settings/features');
	}

	public function actionLineItems(): Response
	{
		return $this->renderSection('line-items');
	}

	/**
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionEditLineItemOptionRule(?string $ruleUid = null): Response
	{
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		$rule = $ruleUid === null ? new LineItemOptionRule() : $this->findLineItemOptionRule($ruleUid);

		if (! $rule instanceof LineItemOptionRule) {
			throw new NotFoundHttpException();
		}

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $this->renderTemplate('foster-checkout/settings/line-items/_edit', [
			'rule' => $rule,
			'isNew' => $ruleUid === null,
			'overridden' => in_array('lineItemOptionRules', $plugin->getOverriddenSettings(), true),
		]);
	}

	public function actionSaveLineItemOptionRule(): ?Response
	{
		$this->requirePostRequest();
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		$this->requireEditableLineItemOptions();

		$postedUid = $this->request->getBodyParam('ruleUid');
		$postedName = $this->request->getBodyParam('setName', '');
		$postedValue = $this->request->getBodyParam('setValue', '');

		$rule = new LineItemOptionRule([
			'uid' => is_string($postedUid) ? $postedUid : null,
			'condition' => $this->request->getBodyParam('condition', []),
			'setName' => is_string($postedName) ? trim($postedName) : '',
			'setValue' => is_string($postedValue) ? trim($postedValue) : '',
		]);

		$rules = $this->lineItemOptionRules();
		$replaced = false;

		foreach ($rules as $position => $existingRule) {
			if ($existingRule->uid !== $rule->uid) {
				continue;
			}

			$rules[$position] = $rule;
			$replaced = true;
		}

		if (! $replaced) {
			$rules[] = $rule;
		}

		return $this->saveLineItemOptionRules($rules);
	}

	public function actionDeleteLineItemOptionRule(): ?Response
	{
		$this->requirePostRequest();
		$this->requireAcceptsJson();
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		$this->requireEditableLineItemOptions();

		$postedId = $this->request->getRequiredBodyParam('id');
		$postedUid = is_string($postedId) ? $postedId : '';

		$rules = array_values(array_filter(
			$this->lineItemOptionRules(),
			static fn (LineItemOptionRule $rule): bool => $rule->uid !== $postedUid
		));

		return $this->saveLineItemOptionRules($rules, true);
	}

	/**
	 * @throws ForbiddenHttpException
	 */
	public function actionReorderLineItemOptionRules(): ?Response
	{
		$this->requirePostRequest();
		$this->requireAcceptsJson();
		$this->requirePermission(FosterCheckout::PERMISSION_MANAGE_SETTINGS);

		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		$this->requireEditableLineItemOptions();

		$postedIds = $this->request->getRequiredBodyParam('ids');

		/** @var list<string> $orderedUids */
		$orderedUids = Json::decode(is_string($postedIds) ? $postedIds : '[]');
		$rulesByUid = ArrayHelper::index($this->lineItemOptionRules(), static fn (LineItemOptionRule $rule): string => $rule->uid);

		$reordered = [];

		foreach ($orderedUids as $orderedUid) {
			if (isset($rulesByUid[$orderedUid])) {
				$reordered[] = $rulesByUid[$orderedUid];
			}
		}

		return $this->saveLineItemOptionRules($reordered, true);
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

		// Without this the save surfaces a raw NotSupportedException 500
		if (! Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'error.adminChangesDisallowed'));
		}

		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		$postedSettings = $this->request->getBodyParam('settings', []);

		if (! is_array($postedSettings)) {
			$postedSettings = [];
		}

		// The form disables overridden fields, but a disabled input is not a control. Dropping them
		// per key rather than rejecting the save lets one overridden key sit beside editable ones.
		$postedSettings = $this->withoutOverriddenSettings($postedSettings, $plugin->getOverriddenSettings());

		// savePluginSettings() persists only the keys it is handed and replaces the whole settings
		// node, so the posted section is merged over what is already stored. The stored config is
		// merged rather than the settings model, which cannot round-trip a gateway note defined as
		// a PHP closure.
		$storedSettings = ProjectConfigHelper::unpackAssociativeArrays(
			(array) (Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE . '.settings') ?? [])
		);

		$allSettings = array_merge($storedSettings, $this->normalizeTables($postedSettings));

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
	/**
	 * @param list<string> $fields
	 */
	private function fieldLayoutFailure(string $messageKey, array $fields, FieldLayout $layout): null
	{
		$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, $messageKey, [
			'fields' => implode(', ', $fields),
		]));

		Craft::$app->getUrlManager()->setRouteParams([
			'fieldLayout' => $layout,
		]);

		return null;
	}

	/**
	 * @return list<string>
	 */
	private function layoutFieldHandles(FieldLayout $layout): array
	{
		$handles = [];

		foreach ($layout->getCustomFieldElements() as $customField) {
			$handles[] = (string) $customField->getField()->handle;
		}

		return $handles;
	}

	private function gateway(string $gatewayHandle): GatewayInterface
	{
		$gateway = $this->commerce()->getGateways()->getGatewayByHandle($gatewayHandle);

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
	private function unsupportedFields(FieldLayout $layout, CheckoutFieldLayouts $checkoutFieldLayouts): array
	{
		$unsupported = [];

		foreach ($layout->getCustomFieldElements() as $customField) {
			$field = $customField->getField();

			if ($checkoutFieldLayouts->fieldInputType($field) === null) {
				$unsupported[] = sprintf('%s (%s)', (string) $field->name, $field::displayName());
			}
		}

		return $unsupported;
	}

	/**
	 * Payment form params sit alongside the field layout rather than in it.
	 */
	private function saveGatewayOptions(string $gatewayHandle): void
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		// The field layout is stored outside plugin settings, so it stays editable. These two do not.
		if (in_array('paymentGateways', $plugin->getOverriddenSettings(), true)) {
			return;
		}

		$storedSettings = ProjectConfigHelper::unpackAssociativeArrays(
			(array) (Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE . '.settings') ?? [])
		);

		$storedGateways = is_array($storedSettings['paymentGateways'] ?? null) ? $storedSettings['paymentGateways'] : [];
		$gateway = is_array($storedGateways[$gatewayHandle] ?? null) ? $storedGateways[$gatewayHandle] : [];

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
		$productTypeHandles = [];

		foreach ($this->commerce()->getProductTypes()->getAllProductTypes() as $productType) {
			if (is_string($productType->handle)) {
				$productTypeHandles[] = $productType->handle;
			}
		}

		return $productTypeHandles;
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
	 * @return array<array-key, mixed>
	 */
	private function normalizeTables(array $postedSettings): array
	{
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

	private function findLineItemOptionRule(string $ruleUid): ?LineItemOptionRule
	{
		foreach ($this->lineItemOptionRules() as $lineItemOptionRule) {
			if ($lineItemOptionRule->uid === $ruleUid) {
				return $lineItemOptionRule;
			}
		}

		return null;
	}

	/**
	 * A config file replaces the whole options node, so a saved rule would never be read back.
	 *
	 * @throws ForbiddenHttpException
	 */
	private function requireEditableLineItemOptions(): void
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		if (in_array('lineItemOptionRules', $plugin->getOverriddenSettings(), true)) {
			throw new ForbiddenHttpException(Craft::t(FosterCheckout::HANDLE, 'settings.lineItemOptions.overridden'));
		}
	}

	/**
	 * @param array<array-key, mixed> $postedSettings
	 * @param list<string> $overridden
	 * @return array<array-key, mixed>
	 */
	private function withoutOverriddenSettings(array $postedSettings, array $overridden, string $prefix = ''): array
	{
		foreach ($postedSettings as $name => $value) {
			$path = $prefix === '' ? (string) $name : "{$prefix}.{$name}";

			if (in_array($path, $overridden, true)) {
				unset($postedSettings[$name]);
				continue;
			}

			if (is_array($value)) {
				$postedSettings[$name] = $this->withoutOverriddenSettings($value, $overridden, $path);
			}
		}

		return $postedSettings;
	}

	/**
	 * @return list<LineItemOptionRule>
	 */
	private function lineItemOptionRules(): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $plugin->getCheckout()->settings()->lineItemOptionRules;
	}

	/**
	 * @param list<LineItemOptionRule> $rules
	 */
	private function saveLineItemOptionRules(array $rules, bool $asJson = false): ?Response
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		$storedSettings = ProjectConfigHelper::unpackAssociativeArrays(
			(array) (Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_PLUGINS . '.' . FosterCheckout::HANDLE . '.settings') ?? [])
		);

		$storedSettings['lineItemOptionRules'] = array_map(
			static fn (LineItemOptionRule $rule): array => $rule->toConfig(),
			$rules
		);

		if (! Craft::$app->getPlugins()->savePluginSettings($plugin, $storedSettings)) {
			if ($asJson) {
				return $this->asFailure(Craft::t(FosterCheckout::HANDLE, 'settings.saveFailed'));
			}

			$this->setFailFlash(Craft::t(FosterCheckout::HANDLE, 'settings.saveFailed'));

			return null;
		}

		if ($asJson) {
			return $this->asSuccess();
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
			'productTypeHandles' => $this->productTypeHandles(),
			'gateways' => $this->commerce()->getGateways()->getAllGateways(),
			'configurableAddressFields' => $plugin->getCheckout()->configurableAddressFields(),
			'checkoutPositions' => CheckoutFieldLayouts::CHECKOUT_POSITIONS,
		]);
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
