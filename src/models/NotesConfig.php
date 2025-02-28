<?php

namespace fostercommerce\fostercheckout\models;

use craft\base\Model;

class NotesConfig extends Model
{
	public NoteConfig $cart;

	public NoteConfig $emptyCart;

	public NoteConfig $login;

	public NoteConfig $email;

	public NoteConfig $address;

	public NoteConfig $shipping;

	public NoteConfig $payment;

	public NoteConfig $order;

	public NoteConfig $mistakeHeading;

	public NoteConfig $mistakeText;

	/**
	 * @param array<mixed, mixed> $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (! isset($this->cart)) {
			$this->cart = new NoteConfig();
		}

		if (! isset($this->emptyCart)) {
			$this->emptyCart = new NoteConfig();
		}

		if (! isset($this->login)) {
			$this->login = new NoteConfig();
		}

		if (! isset($this->email)) {
			$this->email = new NoteConfig();
		}

		if (! isset($this->address)) {
			$this->address = new NoteConfig();
		}

		if (! isset($this->shipping)) {
			$this->shipping = new NoteConfig();
		}

		if (! isset($this->payment)) {
			$this->payment = new NoteConfig();
		}

		if (! isset($this->order)) {
			$this->order = new NoteConfig();
		}

		if (! isset($this->mistakeHeading)) {
			$this->mistakeHeading = new NoteConfig();
		}

		if (! isset($this->mistakeText)) {
			$this->mistakeText = new NoteConfig();
		}
	}
}
