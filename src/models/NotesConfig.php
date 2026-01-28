<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class NotesConfig extends Model
{
	public ValueConfig $cart;

	public ValueConfig $emptyCart;

	public ValueConfig $login;

	public ValueConfig $email;

	public ValueConfig $address;

	public ValueConfig $shipping;

	public ValueConfig $payment;

	public ValueConfig $order;

	public ValueConfig $customersOrderNotes;

	public ValueConfig $globalCheckout;

	public ValueConfig $mistakeHeading;

	public ValueConfig $mistakeText;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (! isset($this->cart)) {
			$this->cart = new ValueConfig();
		}

		if (! isset($this->emptyCart)) {
			$this->emptyCart = new ValueConfig();
		}

		if (! isset($this->login)) {
			$this->login = new ValueConfig();
		}

		if (! isset($this->email)) {
			$this->email = new ValueConfig();
		}

		if (! isset($this->address)) {
			$this->address = new ValueConfig();
		}

		if (! isset($this->shipping)) {
			$this->shipping = new ValueConfig();
		}

		if (! isset($this->payment)) {
			$this->payment = new ValueConfig();
		}

		if (! isset($this->order)) {
			$this->order = new ValueConfig();
		}

		if (! isset($this->customersOrderNotes)) {
			$this->customersOrderNotes = new ValueConfig();
		}

		if (! isset($this->globalCheckout)) {
			$this->globalCheckout = new ValueConfig();
		}

		if (! isset($this->mistakeHeading)) {
			$this->mistakeHeading = new ValueConfig();
		}

		if (! isset($this->mistakeText)) {
			$this->mistakeText = new ValueConfig();
		}
	}
}
