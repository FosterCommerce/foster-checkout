<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Model;

class PaymentGatewayConfig extends Model
{
	/**
	 * @var list<string>
	 */
	public const array FUNDING_SOURCES = [
		'card',
		'credit',
		'paylater',
		'venmo',
		'bancontact',
		'blik',
		'eps',
		'giropay',
		'ideal',
		'mercadopago',
		'mybank',
		'p24',
		'sepa',
		'sofort',
	];

	/**
	 * @var list<string>
	 */
	public const array CARD_BRANDS = [
		'visa',
		'mastercard',
		'amex',
		'discover',
		'jcb',
		'elo',
		'hiper',
	];

	/**
	 * Settings a config file may still name, each with what replaced it.
	 *
	 * @var array<string, string>
	 */
	private const array REMOVED_SETTINGS = [
		'fields' => "the gateway's field layout",
		'params' => 'the settings for each gateway, or the payment form params event',
	];

	public ValueConfig $note;

	/**
	 * What the checkout calls this gateway. Empty uses the gateway's own name.
	 */
	public string $label = '';

	/**
	 * How Stripe's payment element lists the payment methods: `tabs` across the top, `accordion`
	 * stacked with each method expanding in place, or `auto` to let Stripe choose per device.
	 */
	public string $layout = 'tabs';

	/**
	 * Stripe payment method types in the order the payment element lists them, such as `card` or
	 * `apple_pay`. A type on offer but left out sorts after these; one not on offer is ignored.
	 *
	 * @var array<string>
	 */
	public array $paymentMethodOrder = [];

	/**
	 * Whether Stripe offers Link, which saves a customer's details against their phone number for
	 * a later checkout. You can disable it in Stripe's dashboard too.
	 */
	public bool $enableLink = true;

	/**
	 * PayPal funding sources the buttons leave out, such as `paylater` or `venmo`.
	 *
	 * @var array<string>
	 */
	public array $disableFunding = [];

	/**
	 * PayPal funding sources the buttons add, for ones PayPal does not offer by default.
	 *
	 * @var array<string>
	 */
	public array $enableFunding = [];

	/**
	 * Card brands PayPal's own card fields turn away, such as `amex`.
	 *
	 * @var array<string>
	 */
	public array $disableCard = [];

	/**
	 * The locale PayPal's buttons use, such as `en_US`. Empty lets PayPal pick from the buyer's country.
	 */
	public string $locale = '';

	/**
	 * PayPal SDK components to load, such as `buttons,messages`. Empty loads PayPal's own default.
	 */
	public string $components = '';

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(
		public string $handle,
		$config = []
	) {
		parent::__construct($config);

		if (! isset($this->note)) {
			$this->note = new ValueConfig();
		}
	}

	#[\Override]
	public function __set($name, $value): void
	{
		if (array_key_exists($name, self::REMOVED_SETTINGS)) {
			$replacement = self::REMOVED_SETTINGS[$name];
			// The deprecator throws whenever a site sets throwExceptions, which craft-config ties to devMode
			Craft::warning("`{$name}` has been replaced by {$replacement}.", 'deprecation-error');

			return;
		}

		parent::__set($name, $value);
	}

	/**
	 * @return array<array-key, mixed>
	 */
	#[\Override]
	protected function defineRules(): array
	{
		return [
			...parent::defineRules(),
			// An unknown value leaves the payment element unrendered
			[
				'layout',
				'in',
				'range' => ['tabs', 'accordion', 'auto'],
				'strict' => true,
			],
			// An unknown value stops the PayPal buttons rendering
			[
				['disableFunding', 'enableFunding'],
				'in',
				'range' => self::FUNDING_SOURCES,
				'allowArray' => true,
				'strict' => true,
			],
			[
				'disableCard',
				'in',
				'range' => self::CARD_BRANDS,
				'allowArray' => true,
				'strict' => true,
			],
		];
	}
}
