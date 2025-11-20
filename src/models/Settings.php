<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class Settings extends Model
{
	public OptionConfig $options;

	public BrandingConfig $branding;

	public PathConfig $paths;

	/**
	 * Add for each product type using the product type handle, to define the field handles used for the
	 * product and/or variant preview image to display in the cart view
	 *
	 * @var array<string, ProductConfig>
	 */
	public array $products = [];

	/**
	 * Notes that will appear in the cart, login, and checkout steps. Include the element handle (global or single)
	 * and the field handle in that entry that contains the content you want to display. Fields can be either plain
	 * text of rich text fields like Redactor or CKEditor
	 */
	public NotesConfig $notes;

	public LinksConfig $links;

	/**
	 * For each payment gateway using the gateway handle, to define an array of fields to be rendered when
	 * that gateway is selected
	 *
	 * @var array<string, PaymentGatewayConfig>
	 */
	public array $paymentGateways = [];

	public array $zeroValueGatewayHandles = [];

	/**
	 * @param array<array-key, mixed> $config
	 */
	public function __construct(array $config = [])
	{
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

		if (! isset($this->notes)) {
			$this->notes = new NotesConfig();
		}

		if (! isset($this->links)) {
			$this->links = new LinksConfig();
		}
	}

	/**
	 * @param array<mixed, mixed> $values
	 * @param bool $safeOnly
	 */
	public function setAttributes($values, $safeOnly = true): void
	{
		if (array_key_exists('options', $values)) {
			$values['options'] = new OptionConfig($values['options']);
		}

		if (array_key_exists('branding', $values)) {
			$values['branding'] = new BrandingConfig($values['branding']);
		}

		if (array_key_exists('paths', $values)) {
			$values['paths'] = new PathConfig($values['paths']);
		}

		if (array_key_exists('products', $values)) {
			foreach ($values['products'] as &$product) {
				$product = new ProductConfig($product);
			}
		}

		if (array_key_exists('notes', $values)) {
			foreach ($values['notes'] as &$note) {
				$note = new ValueConfig($note);
			}

			$values['notes'] = new NotesConfig($values['notes']);
		}

		if (array_key_exists('paymentGateways', $values)) {
			foreach ($values['paymentGateways'] as $gatewayHandle => $paymentGateway) {
				$values['paymentGateways'][$gatewayHandle] = new PaymentGatewayConfig($gatewayHandle, $paymentGateway);
			}
		}

		if (array_key_exists('links', $values)) {
			foreach ($values['links'] as &$link) {
				$link = new ValueConfig($link);
			}

			$values['links'] = new LinksConfig($values['links']);
		}


		parent::setAttributes($values, $safeOnly);
	}
}
