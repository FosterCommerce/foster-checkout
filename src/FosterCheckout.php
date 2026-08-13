<?php

namespace fostercommerce\fostercheckout;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\commerce\elements\Order;
use craft\commerce\events\OrderNoticeEvent;
use craft\events\DefineAddressFieldLabelEvent;
use craft\events\DefineAddressFieldsEvent;
use craft\events\DefineAddressSubdivisionsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\i18n\PhpMessageSource;
use craft\services\Addresses;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\services\Checkout;
use yii\base\Event;

/**
 * @property-read Checkout $checkout
 */
class FosterCheckout extends Plugin
{
	/**
	 * @var array<string, string>
	 */
	private const CHECKOUT_ROUTES = [
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
	private const UK_COUNTIES = [
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
		];
	}

	protected function createSettingsModel(): ?Model
	{
		return new Settings();
	}

	protected function settingsHtml(): ?string
	{
		return Craft::$app->view->renderTemplate('foster-checkout/_plugin/settings.twig', [
			'plugin' => $this,
			'settings' => $this->getSettings(),
		]);
	}

	private function registerComponents(): void
	{
		$this->setComponents([
			'checkout' => Checkout::class,
		]);
	}

	private function attachEventHandlers(): void
	{
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
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			static function (RegisterTemplateRootsEvent $event): void {
				$event->roots['foster-checkout'] = __DIR__ . '/templates';
			}
		);

		/* Register our site URL rules based on the plugins 'paths' setting */
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

		// Although a county is not actually required for UK addresses (the postal service ignores it)
		// it is normal in the UK to write addresses with a county
		// with that said, UK counties are a minefield so we should not make the field required
		// but simply provide it as an option with a reasonable list of county names

		// Adds the Administrative area to UK addresses
		Event::on(
			Addresses::class,
			Addresses::EVENT_DEFINE_USED_FIELDS,
			static function (DefineAddressFieldsEvent $event): void {
				if ($event->countryCode === 'GB') {
					$event->fields[] = AddressField::ADMINISTRATIVE_AREA;
				}
			}
		);

		// Changes the label of the Administrative area field to "County" for UK addresses
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

		// Commerce persists a coupon notice on the order, so the cart page would have to write
		// on a GET to make it appear only once. A flash survives the redirect and expires itself.
		Event::on(
			Order::class,
			Order::EVENT_BEFORE_APPLY_ADD_NOTICE,
			static function (OrderNoticeEvent $event): void {
				if (! Craft::$app->getRequest()->getIsSiteRequest() || $event->orderNotice->attribute !== 'couponCode') {
					return;
				}

				Craft::$app->getSession()->setFlash('couponCodeError', $event->orderNotice->message);
				$event->isValid = false;
			}
		);

		// A 'reasonable' list of UK county names
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
