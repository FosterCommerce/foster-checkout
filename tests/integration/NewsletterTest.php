<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\elements\User;
use fostercommerce\fostercheckout\models\OptionConfig;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * Whether the newsletter checkbox is offered, which also decides if a signed-in customer sees the stepped email step.
 */
final class NewsletterTest extends CartTestCase
{
	public function testTheNewsletterIsOfferedWhenTrackingAndAListAreSet(): void
	{
		if (! Craft::$app->getPlugins()->isPluginEnabled('klaviyo-connect-plus')) {
			self::markTestSkipped('Klaviyo Connect Plus is not enabled on this site.');
		}

		[, $cart] = $this->signedInCustomerCart();

		self::assertTrue($this->withOptions([], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testTheNewsletterIsNotOfferedWithTrackingOff(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertFalse($this->withOptions([
			'enableKlaviyoTracking' => false,
		], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testTheNewsletterIsNotOfferedWithoutAList(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertFalse($this->withOptions([
			'klaviyoListId' => null,
		], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testTheNewsletterIsNotOfferedToSomeoneOrderingForAnotherCustomer(): void
	{
		[$customer, $cart] = $this->signedInCustomerCart();
		$purchaser = User::find()->id(['not', $customer->id])->one();

		if (! $purchaser instanceof User) {
			self::markTestSkipped('This site has no second user to place the order.');
		}

		Craft::$app->getUser()->setIdentity($purchaser);

		self::assertFalse($this->withOptions([], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testASignedInCustomerSkipsTheEmailStepWithoutTheNewsletter(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertTrue($this->withOptions([
			'enableKlaviyoTracking' => false,
		], fn (): bool => $this->checkout()->skipsEmailStep($cart)));
	}

	public function testAGuestNeverSkipsTheEmailStep(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		Craft::$app->getUser()->setIdentity(null);

		self::assertFalse($this->withOptions([
			'enableKlaviyoTracking' => false,
		], fn (): bool => $this->checkout()->skipsEmailStep($cart)));
	}

	/**
	 * @param array<string, mixed> $options
	 * @param callable(): bool $check
	 */
	private function withOptions(array $options, callable $check): bool
	{
		$settings = $this->settings();
		$storedOptions = $settings->options;
		$settings->options = new OptionConfig([
			'enableKlaviyoTracking' => true,
			'klaviyoListId' => 'TEST_LIST',
			'subscribe' => 'Email me product updates',
			...$options,
		]);

		try {
			return $check();
		} finally {
			$settings->options = $storedOptions;
		}
	}
}
