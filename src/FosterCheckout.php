<?php

namespace fostercommerce\fostercheckout;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Craft;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\base\Plugin;
use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\elements\Order;
use craft\commerce\events\ModifyCartInfoEvent;
use craft\commerce\events\OrderNoticeEvent;
use craft\elements\Address;
use craft\events\DefineAddressFieldLabelEvent;
use craft\events\DefineAddressFieldsEvent;
use craft\events\DefineAddressSubdivisionsEvent;
use craft\events\DefineRulesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\i18n\PhpMessageSource;
use craft\services\Addresses;
use craft\services\UserPermissions;
use craft\web\Request as WebRequest;
use craft\web\Response;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use fostercommerce\fostercheckout\services\Content;
use yii\base\Event;

/**
 * @property-read Checkout $checkout
 * @property-read Content $content
 * @property-read CheckoutFieldLayouts $checkoutFieldLayouts
 */
class FosterCheckout extends Plugin
{
	public const HANDLE = 'foster-checkout';

	public const PERMISSION_VIEW_CONTENT = 'foster-checkout-viewContent';

	public const PERMISSION_EDIT_CONTENT = 'foster-checkout-editContent';

	public const PERMISSION_MANAGE_APPEARANCE = 'foster-checkout-manageAppearance';

	public const PERMISSION_MANAGE_FEATURES = 'foster-checkout-manageFeatures';

	public const PERMISSION_MANAGE_SETTINGS = 'foster-checkout-manageSettings';

	/**
	 * @var array<int, string>
	 */
	private const array SETTINGS_SECTIONS = ['appearance', 'features', 'products', 'gateways', 'fields', 'general'];

	/**
	 * @var array<string, string>
	 */
	private const array CHECKOUT_ROUTES = [
		'' => 'foster-checkout/checkout/index',
		'/email' => 'foster-checkout/checkout/email',
		'/address' => 'foster-checkout/checkout/address',
		'/shipping' => 'foster-checkout/checkout/shipping',
		'/billing' => 'foster-checkout/checkout/billing',
		'/payment' => 'foster-checkout/checkout/payment',
		'/order' => 'foster-checkout/checkout/order',
		'/login' => 'foster-checkout/account/login',
		'/register' => 'foster-checkout/account/register',
	];

	/**
	 * @var array<string, string>
	 */
	private const array UK_COUNTIES = [
		'' => 'N/A',
		'Aberdeenshire' => 'Aberdeenshire',
		'Angus' => 'Angus',
		'Argyll' => 'Argyll',
		'Avon' => 'Avon',
		'Ayrshire' => 'Ayrshire',
		'Banffshire' => 'Banffshire',
		'Bedfordshire' => 'Bedfordshire',
		'Berkshire' => 'Berkshire',
		'Berwickshire' => 'Berwickshire',
		'Buckinghamshire' => 'Buckinghamshire',
		'Caithness' => 'Caithness',
		'Cambridgeshire' => 'Cambridgeshire',
		'Cheshire' => 'Cheshire',
		'Clackmannanshire' => 'Clackmannanshire',
		'Cleveland' => 'Cleveland',
		'Clwyd' => 'Clwyd',
		'Cornwall' => 'Cornwall',
		'County Antrim' => 'County Antrim',
		'County Armagh' => 'County Armagh',
		'County Down' => 'County Down',
		'County Durham' => 'County Durham',
		'County Fermanagh' => 'County Fermanagh',
		'County Londonderry' => 'County Londonderry',
		'County Tyrone' => 'County Tyrone',
		'Cumbria' => 'Cumbria',
		'Derbyshire' => 'Derbyshire',
		'Devon' => 'Devon',
		'Dorset' => 'Dorset',
		'Dumfriesshire' => 'Dumfriesshire',
		'Dunbartonshire' => 'Dunbartonshire',
		'Dyfed' => 'Dyfed',
		'East Lothian' => 'East Lothian',
		'East Sussex' => 'East Sussex',
		'Essex' => 'Essex',
		'Fife' => 'Fife',
		'Gloucestershire' => 'Gloucestershire',
		'Gwent' => 'Gwent',
		'Gwynedd' => 'Gwynedd',
		'Hampshire' => 'Hampshire',
		'Herefordshire' => 'Herefordshire',
		'Hertfordshire' => 'Hertfordshire',
		'Inverness-shire' => 'Inverness-shire',
		'Isle of Arran' => 'Isle of Arran',
		'Isle of Barra' => 'Isle of Barra',
		'Isle of Benbecula' => 'Isle of Benbecula',
		'Isle of Bute' => 'Isle of Bute',
		'Isle of Canna' => 'Isle of Canna',
		'Isle of Coll' => 'Isle of Coll',
		'Isle of Colonsay' => 'Isle of Colonsay',
		'Isle of Cumbrae' => 'Isle of Cumbrae',
		'Isle of Eigg' => 'Isle of Eigg',
		'Isle of Gigha' => 'Isle of Gigha',
		'Isle of Harris' => 'Isle of Harris',
		'Isle of Iona' => 'Isle of Iona',
		'Isle of Islay' => 'Isle of Islay',
		'Isle of Jura' => 'Isle of Jura',
		'Isle of Lewis' => 'Isle of Lewis',
		'Isle of Mull' => 'Isle of Mull',
		'Isle of North Uist' => 'Isle of North Uist',
		'Isle of Rhum' => 'Isle of Rhum',
		'Isle of Scalpay' => 'Isle of Scalpay',
		'Isle of Skye' => 'Isle of Skye',
		'Isle of South Uist' => 'Isle of South Uist',
		'Isle of Tiree' => 'Isle of Tiree',
		'Isle of Wight' => 'Isle of Wight',
		'Kent' => 'Kent',
		'Kincardineshire' => 'Kincardineshire',
		'Kinross-shire' => 'Kinross-shire',
		'Kirkcudbrightshire' => 'Kirkcudbrightshire',
		'Lanarkshire' => 'Lanarkshire',
		'Lancashire' => 'Lancashire',
		'Leicestershire' => 'Leicestershire',
		'Lincolnshire' => 'Lincolnshire',
		'London' => 'London',
		'Merseyside' => 'Merseyside',
		'Mid Glamorgan' => 'Mid Glamorgan',
		'Middlesex' => 'Middlesex',
		'Midlothian' => 'Midlothian',
		'Morayshire' => 'Morayshire',
		'Nairnshire' => 'Nairnshire',
		'Norfolk' => 'Norfolk',
		'North Humberside' => 'North Humberside',
		'North Yorkshire' => 'North Yorkshire',
		'Northamptonshire' => 'Northamptonshire',
		'Northumberland' => 'Northumberland',
		'Nottinghamshire' => 'Nottinghamshire',
		'Orkney' => 'Orkney',
		'Oxfordshire' => 'Oxfordshire',
		'Peeblesshire' => 'Peeblesshire',
		'Perthshire' => 'Perthshire',
		'Powys' => 'Powys',
		'Renfrewshire' => 'Renfrewshire',
		'Ross-shire' => 'Ross-shire',
		'Roxburghshire' => 'Roxburghshire',
		'Selkirkshire' => 'Selkirkshire',
		'Shetland' => 'Shetland',
		'Shropshire' => 'Shropshire',
		'Somerset' => 'Somerset',
		'South Glamorgan' => 'South Glamorgan',
		'South Humberside' => 'South Humberside',
		'South Yorkshire' => 'South Yorkshire',
		'Staffordshire' => 'Staffordshire',
		'Stirlingshire' => 'Stirlingshire',
		'Suffolk' => 'Suffolk',
		'Surrey' => 'Surrey',
		'Sutherland' => 'Sutherland',
		'Tyne and Wear' => 'Tyne and Wear',
		'Warwickshire' => 'Warwickshire',
		'West Glamorgan' => 'West Glamorgan',
		'West Lothian' => 'West Lothian',
		'West Midlands' => 'West Midlands',
		'West Sussex' => 'West Sussex',
		'West Yorkshire' => 'West Yorkshire',
		'Wigtownshire' => 'Wigtownshire',
		'Wiltshire' => 'Wiltshire',
		'Worcestershire' => 'Worcestershire',
	];

	// Craft only runs pending migrations when this is greater than the version it has stored.
	public string $schemaVersion = '1.3.0';

	public bool $hasCpSection = true;

	public bool $hasCpSettings = true;

	// Craft only infers this when getSettingsResponse() is not overridden, and without it the
	// plugin drops out of Settings -> Plugins wherever admin changes are disallowed.
	public bool $hasReadOnlyCpSettings = true;

	private ?string $singlePageCouponCodeError = null;

	#[\Override]
	public function init(): void
	{
		parent::init();

		Craft::setAlias('@fostercheckout', __DIR__);

		// Defer most setup tasks until Craft is fully initialized
		Craft::$app->onInit(function (): void {
			$this->registerComponents();
			$this->attachEventHandlers();
		});

		// Translations
		Craft::$app->i18n->translations['foster-checkout'] = [
			'class' => PhpMessageSource::class,
			'sourceLanguage' => 'en',
			'basePath' => __DIR__ . '/translations',
			'allowOverrides' => true,
			'forceTranslation' => true,
		];
	}

	/**
	 * Top-level settings keys the site's config file sets.
	 *
	 * Craft shallow-merges the file over stored settings, so one key in the file overrides its
	 * whole section.
	 *
	 * @return array<int, string>
	 */
	public function getOverriddenSettings(): array
	{
		$fileConfig = Craft::$app->getConfig()->getConfigFromFile(self::HANDLE);

		return is_array($fileConfig) ? array_keys($fileConfig) : [];
	}

	/**
	 * @return ?array<string, mixed>
	 */
	#[\Override]
	public function getCpNavItem(): ?array
	{
		$navItem = parent::getCpNavItem();

		if (! is_array($navItem)) {
			return null;
		}

		$navItem['label'] = Craft::t(self::HANDLE, 'nav.checkout');
		// Bare handle so any subpath keeps the section highlighted; Craft matches on str_starts_with.
		$navItem['url'] = self::HANDLE;

		$userSession = Craft::$app->getUser();

		if ($userSession->checkPermission(self::PERMISSION_VIEW_CONTENT)) {
			$navItem['subnav']['content'] = [
				'label' => Craft::t(self::HANDLE, 'nav.content'),
				'url' => self::HANDLE . '/content',
			];
		}

		foreach (self::SETTINGS_SECTIONS as $section) {
			if (! $userSession->checkPermission(self::settingsPermission($section))) {
				continue;
			}

			$navItem['subnav'][$section] = [
				'label' => Craft::t(self::HANDLE, "nav.{$section}"),
				'url' => self::HANDLE . "/settings/{$section}",
			];
		}

		return $navItem['subnav'] === [] ? null : $navItem;
	}

	#[\Override]
	public function getSettingsResponse(): mixed
	{
		/** @var Response $response */
		$response = Craft::$app->getResponse();

		return $response->redirect(UrlHelper::cpUrl(self::HANDLE . '/settings/appearance'));
	}

	/**
	 * Craft renders `settingsHtml()` here by default, which this plugin does not implement. The
	 * settings screens disable their own fields when admin changes are off.
	 */
	#[\Override]
	public function getReadOnlySettingsResponse(): mixed
	{
		return $this->getSettingsResponse();
	}

	/**
	 * The top-level config key a settings page edits, or null if the section isn't one of ours.
	 */
	public static function settingsPermission(string $section): string
	{
		return match ($section) {
			'appearance' => self::PERMISSION_MANAGE_APPEARANCE,
			'features' => self::PERMISSION_MANAGE_FEATURES,
			default => self::PERMISSION_MANAGE_SETTINGS,
		};
	}

	#[\Override]
	protected function createSettingsModel(): ?Model
	{
		return new Settings();
	}

	private function singlePageCheckoutPath(): ?string
	{
		if (! $this->checkout->settings()->options->isSinglePageCheckout()) {
			return null;
		}

		$request = Craft::$app->getRequest();
		if (! $request instanceof WebRequest || ! $request->getIsSiteRequest()) {
			return null;
		}

		$checkoutPath = $this->checkout->settings()->paths->checkout;
		$path = $request->getPathInfo();

		if ($path !== $checkoutPath && ! str_starts_with($path, $checkoutPath . '/')) {
			return null;
		}

		return $checkoutPath;
	}

	private function isSinglePageJsonRequest(): bool
	{
		if ($this->singlePageCheckoutPath() === null) {
			return false;
		}

		$request = Craft::$app->getRequest();

		return $request instanceof WebRequest && $request->getAcceptsJson();
	}

	private function registerComponents(): void
	{
		$this->setComponents([
			'checkout' => Checkout::class,
			'content' => Content::class,
			'checkoutFieldLayouts' => CheckoutFieldLayouts::class,
		]);
	}

	private function allowPostieRatesOnSinglePageCheckout(): void
	{
		$checkoutPath = $this->singlePageCheckoutPath();
		if ($checkoutPath === null) {
			return;
		}

		$postie = Craft::$app->getPlugins()->getPlugin('postie');
		if ($postie === null) {
			return;
		}

		$settings = $postie->getSettings();
		if (! is_object($settings) || ! property_exists($settings, 'routesChecks')) {
			return;
		}

		$routesChecks = $settings->routesChecks;
		if (! is_array($routesChecks)) {
			return;
		}

		$route = '/' . $checkoutPath;
		if (in_array($route, $routesChecks, true)) {
			return;
		}

		$settings->routesChecks[] = $route;
	}

	private function allowEmptyPhoneOnSinglePageCartSave(): void
	{
		Event::on(
			Address::class,
			Model::EVENT_AFTER_VALIDATE,
			function (Event $event): void {
				if (! $this->isSinglePageJsonRequest()) {
					return;
				}

				$address = $event->sender;
				if (! $address instanceof Address || ! $address->isFieldEmpty('phone')) {
					return;
				}

				$address->clearErrors('phone');
			}
		);
	}

	/**
	 * The address field layout is shared with the control panel, so a field a store only wants from
	 * customers is required here rather than there.
	 */
	/**
	 * Craft reads required from the order's own field layout, so a checkout layout's own flag needs a rule.
	 */
	private function requireCheckoutFields(): void
	{
		Event::on(
			Order::class,
			Model::EVENT_DEFINE_RULES,
			function (DefineRulesEvent $event): void {
				$request = Craft::$app->getRequest();

				if (! $request instanceof WebRequest || ! $request->getIsSiteRequest()) {
					return;
				}

				// A cart is filled in a step at a time, so these are only required to pay
				$action = $request->getBodyParam('action');

				if (! is_string($action) || ! str_starts_with($action, 'commerce/payments/')) {
					return;
				}

				$order = $event->sender;

				if (! $order instanceof Order) {
					return;
				}

				foreach (CheckoutFieldLayouts::CHECKOUT_POSITIONS as $position) {
					$layout = $this->checkoutFieldLayouts->getCheckoutFieldLayout($position);

					foreach ($layout->getVisibleCustomFieldElements($order) as $layoutElement) {
						if (! $layoutElement->required) {
							continue;
						}

						$field = $layoutElement->getField();

						// A field value can be an object or a bool, which the default emptiness test never
						// counts as empty, so the field decides for itself as it does in Craft's own rules.
						$event->rules[] = [
							"field:{$field->handle}",
							'required',
							'isEmpty' => static fn (mixed $value): bool => $field->isValueEmpty($value, $order),
						];
					}
				}
			}
		);
	}

	private function requireCheckoutAddressFields(): void
	{
		Event::on(
			Address::class,
			Model::EVENT_DEFINE_RULES,
			function (DefineRulesEvent $event): void {
				$request = Craft::$app->getRequest();

				if (! $request instanceof WebRequest || ! $request->getIsSiteRequest()) {
					return;
				}

				$address = $event->sender;

				if (! $address instanceof Address) {
					return;
				}

				$layout = $address->getFieldLayout();

				foreach ($this->checkout->settings()->requiredAddressFields as $attribute) {
					$field = $layout?->getFieldByHandle($attribute);

					// A field value can be an object or a bool, which the default emptiness test never
					// counts as empty, so the field decides for itself as it does in Craft's own rules.
					$event->rules[] = $field instanceof FieldInterface
						? [
							$attribute,
							'required',
							'isEmpty' => static fn (mixed $value): bool => $field->isValueEmpty($value, $address),
						]
						: [$attribute, 'required'];
				}
			}
		);
	}

	private function attachEventHandlers(): void
	{
		$this->registerTwigVariable();
		$this->registerTemplateRoots();
		$this->registerPermissions();
		$this->registerCpRoutes();
		$this->registerSiteRoutes();
		$this->addCountyToUkAddresses();
		$this->labelUkAdministrativeAreaAsCounty();
		$this->addCheckoutStateToCartResponses();
		$this->flashOrderNoticesOnce();
		$this->listUkCounties();
	}

	private function registerTwigVariable(): void
	{
		$this->allowPostieRatesOnSinglePageCheckout();
		$this->allowEmptyPhoneOnSinglePageCartSave();
		$this->requireCheckoutAddressFields();
		$this->requireCheckoutFields();

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			function (Event $e): void {
				/** @var CraftVariable $variable */
				$variable = $e->sender;

				// Attach a service:
				$variable->set('fostercheckout', Checkout::class);
			}
		);

		/* Register our plugins templates directory so Craft knows to look there  */
	}

	private function registerTemplateRoots(): void
	{
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			static function (RegisterTemplateRootsEvent $event): void {
				$event->roots['foster-checkout'] = __DIR__ . '/templates';
			}
		);
	}

	private function registerPermissions(): void
	{
		Event::on(
			UserPermissions::class,
			UserPermissions::EVENT_REGISTER_PERMISSIONS,
			static function (RegisterUserPermissionsEvent $event): void {
				$event->permissions[] = [
					'heading' => Craft::t(self::HANDLE, 'nav.checkout'),
					'permissions' => [
						self::PERMISSION_VIEW_CONTENT => [
							'label' => Craft::t(self::HANDLE, 'permission.viewContent'),
							'nested' => [
								self::PERMISSION_EDIT_CONTENT => [
									'label' => Craft::t(self::HANDLE, 'permission.editContent'),
									'warning' => Craft::t(self::HANDLE, 'permission.editContentWarning'),
								],
							],
						],
						self::PERMISSION_MANAGE_APPEARANCE => [
							'label' => Craft::t(self::HANDLE, 'permission.manageAppearance'),
						],
						self::PERMISSION_MANAGE_FEATURES => [
							'label' => Craft::t(self::HANDLE, 'permission.manageFeatures'),
						],
						self::PERMISSION_MANAGE_SETTINGS => [
							'label' => Craft::t(self::HANDLE, 'permission.manageSettings'),
						],
					],
				];
			}
		);
	}

	private function registerCpRoutes(): void
	{
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			static function (RegisterUrlRulesEvent $event): void {
				$event->rules[self::HANDLE] = self::HANDLE . '/content/edit';
				$event->rules[self::HANDLE . '/content'] = self::HANDLE . '/content/edit';

				foreach (self::SETTINGS_SECTIONS as $section) {
					$event->rules[self::HANDLE . "/settings/{$section}"] = self::HANDLE . "/settings/{$section}";
				}

				$event->rules[self::HANDLE . '/settings/gateways/<gatewayHandle:{handle}>'] = self::HANDLE . '/settings/edit-gateway';
				$event->rules[self::HANDLE . '/settings/fields/<position:{handle}>'] = self::HANDLE . '/settings/edit-field';
			}
		);

		/* Register our site URL rules based on the plugins 'paths' setting */
	}

	private function registerSiteRoutes(): void
	{
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			function (RegisterUrlRulesEvent $event): void {
				// Get the paths from the settings
				$paths = $this->checkout->settings()->paths;
				$checkoutPath = $paths->checkout;

				foreach (self::CHECKOUT_ROUTES as $suffix => $template) {
					$event->rules[$checkoutPath . $suffix] = [
						'template' => $template,
					];
				}

				if ($paths->useCartTemplate) {
					$cartPath = $paths->cart;
					$event->rules[$cartPath] = [
						'template' => 'foster-checkout/cart/index',
					];
				}
			}
		);

		// The postal service ignores the county, but UK addresses are normally written with one.
		// County names are inconsistent enough that the field stays optional.
	}

	// Adds the Administrative area to UK addresses
	private function addCountyToUkAddresses(): void
	{
		Event::on(
			Addresses::class,
			Addresses::EVENT_DEFINE_USED_FIELDS,
			static function (DefineAddressFieldsEvent $event): void {
				if ($event->countryCode === 'GB') {
					$event->fields[] = AddressField::ADMINISTRATIVE_AREA;
				}
			}
		);
	}

	// Changes the label of the Administrative area field to "County" for UK addresses
	private function labelUkAdministrativeAreaAsCounty(): void
	{
		Event::on(
			Addresses::class,
			Addresses::EVENT_DEFINE_FIELD_LABEL,
			static function (DefineAddressFieldLabelEvent $event): void {
				if (
					$event->countryCode === 'GB' &&
					$event->field === AddressField::ADMINISTRATIVE_AREA
				) {
					$event->label = 'County';
				}
			}
		);
	}

	private function addCheckoutStateToCartResponses(): void
	{
		Event::on(
			BaseFrontEndController::class,
			BaseFrontEndController::EVENT_MODIFY_CART_INFO,
			function (ModifyCartInfoEvent $event): void {
				if (! $this->isSinglePageJsonRequest()) {
					return;
				}

				$cart = $event->cart;
				if (! $cart instanceof Order) {
					return;
				}

				$live = $this->checkout->checkoutLiveState($cart);

				if ($this->singlePageCouponCodeError !== null) {
					$live['couponCodeError'] = $this->singlePageCouponCodeError;
					$this->singlePageCouponCodeError = null;
				}

				$event->cartInfo['fosterCheckout'] = $live;
			}
		);
	}

	// Commerce persists a coupon notice on the order, so the cart page would have to write
	// on a GET to make it appear only once. A flash survives the redirect and expires itself.
	private function flashOrderNoticesOnce(): void
	{
		Event::on(
			Order::class,
			Order::EVENT_BEFORE_APPLY_ADD_NOTICE,
			function (OrderNoticeEvent $event): void {
				if (! Craft::$app->getRequest()->getIsSiteRequest() || $event->orderNotice->attribute !== 'couponCode') {
					return;
				}

				if ($this->isSinglePageJsonRequest()) {
					$this->singlePageCouponCodeError = $event->orderNotice->message;
				} else {
					Craft::$app->getSession()->setFlash('couponCodeError', $event->orderNotice->message);
				}

				$event->isValid = false;
			}
		);
	}

	// A 'reasonable' list of UK county names
	private function listUkCounties(): void
	{
		Event::on(
			Addresses::class,
			Addresses::EVENT_DEFINE_ADDRESS_SUBDIVISIONS,
			static function (DefineAddressSubdivisionsEvent $event): void {
				if (count($event->parents) === 1 && $event->parents[0] === 'GB') {
					$event->subdivisions = self::UK_COUNTIES;
				}
			}
		);
	}
}
