<?php

namespace fostercommerce\fostercheckout\services;

use Craft;
use craft\commerce\base\Gateway;
use craft\commerce\base\GatewayInterface;
use craft\commerce\elements\Order;
use craft\commerce\gateways\Manual;
use craft\commerce\helpers\Currency;
use craft\commerce\models\PaymentSource;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use DateTime;
use fostercommerce\fostercheckout\FosterCheckout;
use yii\base\Component;

/**
 * @since 2.1.0
 */
class PaymentRequests extends Component
{
	public const string SYSTEM_MESSAGE_KEY = 'foster_checkout_payment_request';

	public const string TOKEN_PARAM = 'paymentRequest';

	public const string PERMISSION = 'commerce-editOrders';

	private const int LINK_LIFETIME_DAYS = 30;

	public function canRequest(Order $order): bool
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return $order->isCompleted && $order->hasOutstandingBalance() && ! $plugin->getCheckout()->receipt($order)->refundedInFull;
	}

	public function balance(Order $order): string
	{
		return Currency::formatAsCurrency($order->getOutstandingBalance(), $order->currency);
	}

	/**
	 * Leaves out Manual gateways, which record a balance as authorized without taking money.
	 *
	 * @return list<Gateway>
	 */
	public function gateways(Order $order): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return array_values(array_filter(
			$plugin->getCheckout()->paymentGateways($order),
			static fn (Gateway $gateway): bool => ! $gateway instanceof Manual,
		));
	}

	/**
	 * Email the customer a link to pay the order's balance.
	 */
	public function send(Order $order): bool
	{
		$expires = DateTimeHelper::now()->modify('+' . self::LINK_LIFETIME_DAYS . ' days');

		/** @var Message $message */
		$message = Craft::$app->getMailer()->composeFromKey(self::SYSTEM_MESSAGE_KEY, [
			'order' => $order,
			'amount' => $this->balance($order),
			'link' => $this->url($order, $expires),
			'expires' => $expires,
		]);
		$message->siteId = $order->orderSiteId;
		$message->setTo((string) $order->email);

		return $message->send();
	}

	/**
	 * Null for a changed or expired token.
	 */
	public function orderForToken(string $token): ?Order
	{
		$data = Craft::$app->getSecurity()->validateData(StringHelper::base64UrlDecode($token));
		if (! is_string($data)) {
			return null;
		}

		/** @var array{number: string, expires: int} $payload */
		$payload = Json::decode($data);
		if ($payload['expires'] < time()) {
			return null;
		}

		/** @var Order|null $order */
		$order = Order::find()->number($payload['number'])->isCompleted(true)->one();

		return $order;
	}

	/**
	 * The saved payment methods the signed-in user can charge to the order: their own on their own order,
	 * plus those a `Checkout::EVENT_DEFINE_SAVED_PAYMENT_SOURCES` handler adds.
	 *
	 * @return array<int, PaymentSource>
	 */
	public function paymentSources(Order $order, GatewayInterface $gateway): array
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();
		$checkout = $plugin->getCheckout();
		$currentUser = Craft::$app->getUser()->getIdentity();

		if (! $checkout->settings()->enableSavedPaymentSources || ! $currentUser instanceof User) {
			return [];
		}

		// Start empty unless the signed-in user is the customer, since anyone with the link can open the pay page
		return $checkout->savedPaymentSources($order, $gateway, $currentUser->id === $order->getCustomerId() ? null : [])->paymentSources;
	}

	public function payPageUrl(string $token, ?int $siteId = null): string
	{
		/** @var FosterCheckout $plugin */
		$plugin = FosterCheckout::getInstance();

		return UrlHelper::siteUrl($plugin->getCheckout()->settings()->paths->checkout . '/pay', [
			self::TOKEN_PARAM => $token,
		], null, $siteId);
	}

	private function url(Order $order, DateTime $expires): string
	{
		// Encode the token, since Commerce renders a return URL's braces as a template
		$token = StringHelper::base64UrlEncode(Craft::$app->getSecurity()->hashData(Json::encode([
			'number' => $order->number,
			'expires' => $expires->getTimestamp(),
		])));

		return $this->payPageUrl($token, $order->orderSiteId);
	}
}
