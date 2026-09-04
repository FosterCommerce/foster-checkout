<?php

namespace fostercommerce\fostercheckout\plugin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

trait Routes
{
	private function registerCpRoutes(): void
	{
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			static function (RegisterUrlRulesEvent $event): void {
				$event->rules[self::HANDLE] = self::HANDLE . '/content/edit';
				$event->rules[self::HANDLE . '/content'] = self::HANDLE . '/content/edit';

				foreach (self::SETTINGS_SECTIONS as $section) {
					$event->rules[self::HANDLE . "/settings/{$section}"] = self::HANDLE . "/settings/{$section}";
				}

				$event->rules[self::HANDLE . '/settings/test-address-lookup'] = self::HANDLE . '/settings/test-address-lookup';
				$event->rules[self::HANDLE . '/settings/gateways/<gatewayHandle:{handle}>'] = self::HANDLE . '/settings/edit-gateway';
				$event->rules[self::HANDLE . '/settings/fields/<position:{handle}>'] = self::HANDLE . '/settings/edit-field';
				$event->rules[self::HANDLE . '/settings/line-items/new'] = self::HANDLE . '/settings/edit-line-item-option-rule';
				$event->rules[self::HANDLE . '/settings/line-items/<ruleUid:{uid}>'] = self::HANDLE . '/settings/edit-line-item-option-rule';
			}
		);
	}

	private function registerSiteRoutes(): void
	{
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			function (RegisterUrlRulesEvent $event): void {
				$paths = $this->getCheckout()->settings()->paths;
				$checkoutPath = $paths->checkout;

				foreach (self::CHECKOUT_ROUTES as $suffix => $template) {
					$event->rules[$checkoutPath . $suffix] = [
						'template' => $template,
					];
				}

				if ($paths->useCartTemplate) {
					$cartPath = $paths->cart;
					$event->rules[$cartPath] = [
						'template' => 'foster-checkout/cart/index',
					];
				}
			}
		);
	}
}
