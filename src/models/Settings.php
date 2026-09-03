<?php

namespace fostercommerce\fostercheckout\models;

use Craft;
use craft\base\Field;
use craft\base\Model;
use craft\web\View;

class Settings extends Model
{
	/**
	 * How checkout content varies across sites, using Craft's field translation methods:
	 * `none` for one shared copy, `site` for a copy per site, or `language` to share a copy
	 * between sites speaking the same language.
	 */
	public string $contentTranslationMethod = Field::TRANSLATION_METHOD_SITE;

	public OptionConfig $options;

	public LineItemConfig $lineItems;

	/**
	 * Held outside `options` so an `options` block in a config file cannot shadow rules built in the CP.
	 *
	 * @var list<LineItemOptionRule> applied in order, each testing the option as stored
	 */
	public array $lineItemOptionRules = [];

	public BrandingConfig $branding;

	public PathConfig $paths;

	public IncludesConfig $includes;

	/**
	 * Add for each product type using the product type handle, to define the field handles used for the
	 * product and/or variant preview image to display in the cart view
	 *
	 * @var array<string, ProductConfig>
	 */
	public array $products = [];

	/**
	 * Handle of the field on Orders holding the note a customer leaves with their order.
	 */
	public ?string $customerOrderNotesFieldHandle = null;

	/**
	 * For each payment gateway using the gateway handle, to define an array of fields to be rendered when
	 * that gateway is selected
	 *
	 * @var array<string, PaymentGatewayConfig>
	 */
	public array $paymentGateways = [];

	/**
	 * Array of payment gateway handles that should be available for zero value orders
	 *
	 * @var array<string>
	 */
	public array $zeroValueGatewayHandles = [];

	/**
	 * Array of country codes that will be shown first in the country select dropdowns
	 *
	 * @var array<string>
	 */
	public array $priorityCountries = [];

	/**
	 * Address fields to leave off the checkout, named by attribute or custom field handle. A field
	 * the address layout marks required is always shown, so Craft can still validate the address.
	 *
	 * @var array<string>
	 */
	public array $hiddenAddressFields = [];

	/**
	 * Address fields to require at the checkout beyond what the address layout asks for, named by
	 * attribute or custom field handle. A hidden field is never required, since it is not rendered.
	 *
	 * @var array<string>
	 */
	public array $requiredAddressFields = [];

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
		$this->lineItems = new LineItemConfig();
		parent::__construct($config);

		if (! isset($this->options)) {
			$this->options = new OptionConfig();
		}

		if (! isset($this->branding)) {
			$this->branding = new BrandingConfig();
		}

		if (! isset($this->paths)) {
			$this->paths = new PathConfig();
		}

		if (! isset($this->includes)) {
			$this->includes = new IncludesConfig();
		}
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	#[\Override]
	public function rules(): array
	{
		return [
			['includes', 'validateIncludes'],
		];
	}

	/**
	 * An include pointing at a template that does not exist throws while rendering every cart and
	 * checkout page, so it is rejected on save rather than taking the storefront down.
	 */
	public function validateIncludes(string $attribute): void
	{
		$view = Craft::$app->getView();

		foreach (['head', 'body'] as $position) {
			$templatePath = $this->includes->{$position};
			if ($templatePath === '') {
				continue;
			}

			if ($view->doesTemplateExist($templatePath, View::TEMPLATE_MODE_SITE)) {
				continue;
			}

			// Keyed per position so the error renders under the field that holds the bad path.
			$this->addError("{$attribute}.{$position}", Craft::t('foster-checkout', 'settings.general.includeMissing', [
				'path' => $templatePath,
			]));
		}
	}

	/**
	 * @param array<mixed, mixed> $values
	 * @param bool $safeOnly
	 */
	#[\Override]
	public function setAttributes($values, $safeOnly = true): void
	{
		$values = self::moveLineItemSettings($values);

		if (array_key_exists('options', $values)) {
			$values['options'] = new OptionConfig($values['options']);
		}

		if (array_key_exists('lineItems', $values)) {
			$values['lineItems'] = new LineItemConfig($values['lineItems']);
		}

		if (array_key_exists('lineItemOptionRules', $values)) {
			$values['lineItemOptionRules'] = array_map(
				static fn (mixed $rule): LineItemOptionRule => $rule instanceof LineItemOptionRule
					? $rule
					: new LineItemOptionRule(is_array($rule) ? $rule : []),
				array_values((array) $values['lineItemOptionRules'])
			);
		}

		if (array_key_exists('branding', $values)) {
			$values['branding'] = new BrandingConfig($values['branding']);
		}

		if (array_key_exists('paths', $values)) {
			$values['paths'] = new PathConfig($values['paths']);
		}

		if (array_key_exists('includes', $values)) {
			$values['includes'] = new IncludesConfig($values['includes']);
		}

		if (array_key_exists('products', $values)) {
			foreach ($values['products'] as &$product) {
				$product = new ProductConfig($product);
			}

			unset($product);
		}

		// `notes` and `links` moved to content storage so admins can edit them on production.
		// The one developer setting they held is carried over, so existing config files keep working.
		if (array_key_exists('notes', $values)) {
			$fieldHandle = $values['notes']['customersOrderNotes']['fieldHandle'] ?? null;

			if (is_string($fieldHandle) && ! isset($values['customerOrderNotesFieldHandle'])) {
				$values['customerOrderNotesFieldHandle'] = $fieldHandle;
			}

			unset($values['notes']);
		}

		unset($values['links']);

		if (array_key_exists('paymentGateways', $values)) {
			foreach ($values['paymentGateways'] as $gatewayHandle => $paymentGateway) {
				$values['paymentGateways'][$gatewayHandle] = new PaymentGatewayConfig(
					$gatewayHandle,
					[
						// Field widths replaced the per-gateway column count, so a config file still
						// setting it would fatal on an unknown property.
						...array_diff_key($paymentGateway, [
							'columns' => null,
						]),
						'fields' => $paymentGateway['fields'] ?? [],
						'note' => new ValueConfig($paymentGateway['note'] ?? []),
					]
				);
			}
		}

		parent::setAttributes($values, $safeOnly);
	}

	/**
	 * These four settings used to sit in `options`, which is one node a config file replaces whole.
	 *
	 * @param array<mixed, mixed> $values
	 * @return array<mixed, mixed>
	 */
	public static function moveLineItemSettings(array $values): array
	{
		$options = $values['options'] ?? null;

		if (! is_array($options)) {
			return $values;
		}

		$lineItems = is_array($values['lineItems'] ?? null) ? $values['lineItems'] : [];

		foreach (['showLineItemSku', 'enableLineItemOptions', 'hiddenLineItemOptionPrefix', 'lineItemOptionValueMaxLength'] as $setting) {
			if (! array_key_exists($setting, $options)) {
				continue;
			}

			$lineItems[$setting] ??= $options[$setting];
			unset($options[$setting]);
		}

		$values['options'] = $options;

		if ($lineItems !== []) {
			$values['lineItems'] = $lineItems;
		}

		return $values;
	}
}
