<?php

namespace fostercommerce\fostercheckout\plugin;

use fostercommerce\fostercheckout\services\AddressLookup;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\services\CheckoutFieldLayouts;
use fostercommerce\fostercheckout\services\Content;
use yii\base\InvalidConfigException;

trait Services
{
	/**
	 * @throws InvalidConfigException
	 */
	public function getAddressLookup(): AddressLookup
	{
		$service = $this->get('addressLookup');

		if (! $service instanceof AddressLookup) {
			throw new InvalidConfigException('The `addressLookup` service is not configured.');
		}

		return $service;
	}

	/**
	 * @throws InvalidConfigException
	 */
	public function getCheckout(): Checkout
	{
		$service = $this->get('checkout');

		if (! $service instanceof Checkout) {
			throw new InvalidConfigException('The `checkout` service is not configured.');
		}

		return $service;
	}

	/**
	 * @throws InvalidConfigException
	 */
	public function getContent(): Content
	{
		$service = $this->get('content');

		if (! $service instanceof Content) {
			throw new InvalidConfigException('The `content` service is not configured.');
		}

		return $service;
	}

	/**
	 * @throws InvalidConfigException
	 */
	public function getCheckoutFieldLayouts(): CheckoutFieldLayouts
	{
		$service = $this->get('checkoutFieldLayouts');

		if (! $service instanceof CheckoutFieldLayouts) {
			throw new InvalidConfigException('The `checkoutFieldLayouts` service is not configured.');
		}

		return $service;
	}

	private function registerComponents(): void
	{
		$this->setComponents([
			'addressLookup' => AddressLookup::class,
			'checkout' => Checkout::class,
			'content' => Content::class,
			'checkoutFieldLayouts' => CheckoutFieldLayouts::class,
		]);
	}
}
