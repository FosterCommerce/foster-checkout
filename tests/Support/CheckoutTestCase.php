<?php

namespace fostercommerce\fostercheckout\tests\Support;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\web\Request as WebRequest;
use fostercommerce\fostercheckout\FosterCheckout;
use fostercommerce\fostercheckout\models\Settings;
use fostercommerce\fostercheckout\services\Checkout;
use PHPUnit\Framework\TestCase;
use yii\base\Event;

abstract class CheckoutTestCase extends TestCase
{
	/**
	 * @var list<array{0: string, 1: callable}>
	 */
	private array $checkoutHandlers = [];

	#[\Override]
	protected function tearDown(): void
	{
		foreach ($this->checkoutHandlers as [$eventName, $handler]) {
			Event::off(Checkout::class, $eventName, $handler);
		}

		$this->checkoutHandlers = [];

		// A test that signed someone in would otherwise decide what the next one sees
		Craft::$app->getUser()->setIdentity(null);

		parent::tearDown();
	}

	/**
	 * Remove only this handler afterwards, since a site module may have its own on the same event.
	 */
	protected function onCheckoutEvent(string $eventName, callable $handler): void
	{
		Event::on(Checkout::class, $eventName, $handler);
		$this->checkoutHandlers[] = [$eventName, $handler];
	}

	/**
	 * A site whose own module answers the event has no unhandled case to assert.
	 */
	protected function requireNoCheckoutHandler(string $eventName): void
	{
		if ($this->checkout()->hasEventHandlers($eventName)) {
			self::markTestSkipped("A module on this site already handles {$eventName}.");
		}
	}

	/**
	 * Skip rather than fail, since the suite also runs from the plugin repo with no site installed.
	 */
	protected function plugin(): FosterCheckout
	{
		$plugin = FosterCheckout::getInstance();

		if (! $plugin instanceof FosterCheckout) {
			self::markTestSkipped('Foster Checkout is not installed here. Run this suite from a site that has it.');
		}

		return $plugin;
	}

	protected function checkout(): Checkout
	{
		return $this->plugin()->getCheckout();
	}

	protected function settings(): Settings
	{
		/** @var Settings $settings */
		$settings = $this->plugin()->getSettings();

		return $settings;
	}

	protected function commerce(): Commerce
	{
		/** @var Commerce $commerce */
		$commerce = Commerce::getInstance();

		return $commerce;
	}

	/**
	 * @param array<string, mixed> $bodyParams
	 */
	protected function cartUpdateRequest(array $bodyParams): WebRequest
	{
		$request = new WebRequest();
		$request->setBodyParams($bodyParams);

		return $request;
	}
}
