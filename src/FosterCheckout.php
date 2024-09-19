<?php

namespace fostercommerce\craftfostercheckout;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\DefineAddressFieldLabelEvent;
use craft\events\DefineAddressFieldsEvent;
use craft\events\DefineAddressSubdivisionsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Addresses;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use fostercommerce\craftfostercheckout\models\Settings;
use fostercommerce\craftfostercheckout\services\Checkout;
use fostercommerce\craftfostercheckout\variables\Variables;
use yii\base\Event;

/**
 * Foster Checkout plugin
 *
 * @method static FosterCheckout getInstance()
 * @method Settings getSettings()
 * @author Foster Commerce <support@fostercommerce.com>
 * @copyright Foster Commerce
 * @license MIT
 * @property-read Checkout $checkout
 */
class FosterCheckout extends Plugin
{
	public string $schemaVersion = '1.0.0';

	public bool $hasCpSettings = false;

	public function init(): void
	{
		parent::init();

		Craft::setAlias('@fostercheckout', __DIR__);

		// Defer most setup tasks until Craft is fully initialized
		Craft::$app->onInit(function (): void {
			$this->registerComponents();
			$this->attachEventHandlers();
			$this->registerCustomVariables();
		});
	}

	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
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

	private function registerCustomVariables(): void
	{
		// Register the variables
		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			static function (Event $event): void {
				$variable = $event->sender;
				$variable->set('fostercheckout', Variables::class);
			}
		);
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
				$variable->set('checkout', Checkout::class);
			}
		);

		/* Register our plugins templates directory so Craft knows to look there  */
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			function (RegisterTemplateRootsEvent $event): void {
				$event->roots['foster-checkout'] = __DIR__ . '/templates';
			}
		);

		/* Register our site URL rules based on the plugins 'paths' setting */
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			function (RegisterUrlRulesEvent $event): void {
				// Get the paths from the settings
				$paths = $this->checkout->paths();
				$checkoutPath = $paths['checkout'] ?? 'checkout';
				$cartPath = $paths['cart'] ?? 'cart';

				// Define the site URL rules to route to our plugins templates
				$event->rules[$checkoutPath] = [
					'template' => 'foster-checkout/checkout/index',
				];
				$event->rules[$checkoutPath . '/email'] = [
					'template' => 'foster-checkout/checkout/email',
				];
				$event->rules[$checkoutPath . '/address'] = [
					'template' => 'foster-checkout/checkout/address',
				];
				$event->rules[$checkoutPath . '/shipping'] = [
					'template' => 'foster-checkout/checkout/shipping',
				];
				$event->rules[$checkoutPath . '/payment'] = [
					'template' => 'foster-checkout/checkout/payment',
				];
				$event->rules[$checkoutPath . '/order'] = [
					'template' => 'foster-checkout/checkout/order',
				];
				$event->rules[$checkoutPath . '/login'] = [
					'template' => 'foster-checkout/account/login',
				];
				$event->rules[$checkoutPath . '/register'] = [
					'template' => 'foster-checkout/account/register',
				];
				$event->rules[$cartPath] = [
					'template' => 'foster-checkout/cart/index',
				];

				// TODO : Just a page to test components in isolation (remove later)
				$event->rules[$checkoutPath . '/components'] = [
					'template' => 'foster-checkout/_component-test',
				];
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
			function (DefineAddressFieldsEvent $event): void {
				if ($event->countryCode === 'GB') {
					$event->fields[] = AddressField::ADMINISTRATIVE_AREA;
				}
			}
		);

		// Changes the label of the Administrative area field to "County" for UK addresses
		Event::on(
			Addresses::class,
			Addresses::EVENT_DEFINE_FIELD_LABEL,
			function (DefineAddressFieldLabelEvent $event): void {
				if (
					$event->countryCode === 'GB' &&
					$event->field === AddressField::ADMINISTRATIVE_AREA
				) {
					$event->label = 'County';
				}
			}
		);

		// A 'reasonable' list of UK county names
		Event::on(
			Addresses::class,
			Addresses::EVENT_DEFINE_ADDRESS_SUBDIVISIONS,
			function (DefineAddressSubdivisionsEvent $event): void {
				if (count($event->parents) === 1 && $event->parents[0] === 'GB') {
					$event->subdivisions = [
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
				}
			}
		);
	}
}
