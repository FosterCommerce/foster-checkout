<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class NotesConfig extends Model
{
	public TextConfig $cart;

	public TextConfig $emptyCart;

	public TextConfig $login;

	public TextConfig $email;

	public TextConfig $address;

	public TextConfig $shipping;

	public TextConfig $payment;

	public TextConfig $order;

	public TextConfig $mistakeHeading;

	public TextConfig $mistakeText;

	/**
	 * @param array<mixed, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (! isset($this->cart)) {
			$this->cart = new TextConfig();
		}

		if (! isset($this->emptyCart)) {
			$this->emptyCart = new TextConfig();
		}

		if (! isset($this->login)) {
			$this->login = new TextConfig();
		}

		if (! isset($this->email)) {
			$this->email = new TextConfig();
		}

		if (! isset($this->address)) {
			$this->address = new TextConfig();
		}

		if (! isset($this->shipping)) {
			$this->shipping = new TextConfig();
		}

		if (! isset($this->payment)) {
			$this->payment = new TextConfig();
		}

		if (! isset($this->order)) {
			$this->order = new TextConfig();
		}

		if (! isset($this->mistakeHeading)) {
			$this->mistakeHeading = new TextConfig();
		}

		if (! isset($this->mistakeText)) {
			$this->mistakeText = new TextConfig();
		}
	}
}
