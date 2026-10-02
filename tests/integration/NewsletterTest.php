<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\elements\User;
use fostercommerce\fostercheckout\events\NewsletterSubscribeEvent;
use fostercommerce\fostercheckout\models\OptionConfig;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;

/**
 * Whether the newsletter checkbox is offered, which also decides if a signed-in customer sees the stepped email step.
 */
final class NewsletterTest extends CartTestCase
{
	public function testTheNewsletterIsOfferedThroughKlaviyoConnectWithAList(): void
	{
		$klaviyoConnect = Craft::$app->getPlugins()->getPlugin('klaviyoconnect');

		if ($klaviyoConnect === null || version_compare($klaviyoConnect->getVersion(), '7.3.0', '<')) {
			self::markTestSkipped('Klaviyo Connect 7.3.0 or later is not enabled on this site.');
		}

		[, $cart] = $this->signedInCustomerCart();

		self::assertTrue($this->withOptions([], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testASiteHandlerOffersTheNewsletterWithoutKlaviyoOrAList(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$this->onCheckoutEvent(Checkout::EVENT_NEWSLETTER_SUBSCRIBE, static function (NewsletterSubscribeEvent $event): void {
			$event->handled = true;
		});

		self::assertTrue($this->withOptions([
			'newsletterListId' => null,
		], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testASiteHandlerReceivesTheSignUp(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		$received = null;
		$this->onCheckoutEvent(Checkout::EVENT_NEWSLETTER_SUBSCRIBE, static function (NewsletterSubscribeEvent $event) use (&$received): void {
			$received = $event;
			$event->handled = true;
		});

		$this->withOptions([], fn () => $this->checkout()->subscribeToNewsletter($cart, 'subscriber@example.com'));

		self::assertInstanceOf(NewsletterSubscribeEvent::class, $received);
		self::assertSame('subscriber@example.com', $received->email);
		self::assertSame('TEST_LIST', $received->listId);
		self::assertSame($cart, $received->order);
	}

	public function testTheNewsletterIsNotOfferedWhenTurnedOff(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertFalse($this->withOptions([
			'enableNewsletter' => false,
		], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testTheNewsletterIsNotOfferedWithoutASubscriber(): void
	{
		$this->requireNoCheckoutHandler(Checkout::EVENT_NEWSLETTER_SUBSCRIBE);
		[, $cart] = $this->signedInCustomerCart();

		self::assertFalse($this->withOptions([
			'newsletterListId' => null,
		], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testTheNewsletterIsNotOfferedToSomeoneOrderingForAnotherCustomer(): void
	{
		[$customer, $cart] = $this->signedInCustomerCart();
		$purchaser = User::find()->id(['not', $customer->id])->one();

		if (! $purchaser instanceof User) {
			self::markTestSkipped('This site has no second user to place the order.');
		}

		$this->onCheckoutEvent(Checkout::EVENT_NEWSLETTER_SUBSCRIBE, static function (NewsletterSubscribeEvent $event): void {
			$event->handled = true;
		});
		Craft::$app->getUser()->setIdentity($purchaser);

		self::assertFalse($this->withOptions([], fn (): bool => $this->checkout()->offersNewsletter($cart)));
	}

	public function testASignedInCustomerSkipsTheEmailStepWithoutTheNewsletter(): void
	{
		[, $cart] = $this->signedInCustomerCart();

		self::assertTrue($this->withOptions([
			'enableNewsletter' => false,
		], fn (): bool => $this->checkout()->skipsEmailStep($cart)));
	}

	public function testAGuestNeverSkipsTheEmailStep(): void
	{
		[, $cart] = $this->signedInCustomerCart();
		Craft::$app->getUser()->setIdentity(null);

		self::assertFalse($this->withOptions([
			'enableNewsletter' => false,
		], fn (): bool => $this->checkout()->skipsEmailStep($cart)));
	}

	/**
	 * @template TResult
	 * @param array<string, mixed> $options
	 * @param callable(): TResult $check
	 * @return TResult
	 */
	private function withOptions(array $options, callable $check): mixed
	{
		$settings = $this->settings();
		$storedOptions = $settings->options;
		$settings->options = new OptionConfig([
			'enableNewsletter' => true,
			'newsletterListId' => 'TEST_LIST',
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
