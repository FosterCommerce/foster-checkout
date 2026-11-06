<?php

namespace fostercommerce\fostercheckout\tests\integration;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\helpers\Currency;
use craft\commerce\models\Transaction;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\elements\User;
use craft\helpers\Json;
use fostercommerce\fostercheckout\events\DefinePartialPaymentEvent;
use fostercommerce\fostercheckout\services\Checkout;
use fostercommerce\fostercheckout\services\PartialPayments;
use fostercommerce\fostercheckout\tests\Support\CartTestCase;
use fostercommerce\netterms\enums\EntryStatus;
use fostercommerce\netterms\gateways\NetTerms;
use fostercommerce\netterms\helpers\Amounts;
use fostercommerce\netterms\models\Account;
use fostercommerce\netterms\models\Buyer;
use fostercommerce\netterms\Plugin as NetTermsPlugin;
use Money\Currency as MoneyCurrency;
use yii\base\UserException;

/**
 * @since 2.1.0
 */
final class PartialPaymentsTest extends CartTestCase
{
	private string $originalLanguage;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->originalLanguage = Craft::$app->language;
	}

	#[\Override]
	protected function tearDown(): void
	{
		Craft::$app->language = $this->originalLanguage;

		parent::tearDown();
	}

	public function testAnAuthorizationCoversTheBalanceUntilItIsRemoved(): void
	{
		[$cart, $gateway, $transaction] = $this->cartWithAuthorization();

		$partialPayments = $this->partialPayments();
		self::assertSame(10.0, $partialPayments->appliedAmount($cart));
		self::assertSame(10.0, $partialPayments->authorizedAmount($cart));
		self::assertTrue($partialPayments->hasApplied($cart));

		$partialPayments->remove($cart, (int) $gateway->id, $transaction->id);

		self::assertSame(0.0, $partialPayments->appliedAmount($cart));
		self::assertFalse($partialPayments->hasApplied($cart));
		self::assertNotSame(EntryStatus::Authorized, $gateway->getPartialAuthorization($transaction)?->status);
	}

	public function testRemovingAnAlreadyRemovedAuthorizationLeavesItsMessage(): void
	{
		[$cart, $gateway, $transaction] = $this->cartWithAuthorization();

		$partialPayments = $this->partialPayments();
		$partialPayments->remove($cart, (int) $gateway->id, $transaction->id);

		$savedMessage = $this->savedMessage($transaction);

		Craft::$app->language = 'de';
		$partialPayments->remove($cart, (int) $gateway->id, $transaction->id);

		self::assertSame($savedMessage, $this->savedMessage($transaction));
	}

	public function testARemovalIsRecordedInThePrimarySiteLanguage(): void
	{
		[$cart, $gateway, $transaction] = $this->cartWithAuthorization();

		$customerLanguage = $this->languageOtherThanPrimarySite();
		Craft::$app->language = $customerLanguage;
		$this->partialPayments()->remove($cart, (int) $gateway->id, $transaction->id);

		$this->assertStaffMessage('partialPayment.authorizationReleasedByCustomer', $customerLanguage, $transaction);
	}

	public function testAReleaseAfterCartChangesIsRecordedInThePrimarySiteLanguage(): void
	{
		[$cart, , $transaction] = $this->cartWithAuthorization();
		$this->onCheckoutEvent(Checkout::EVENT_DEFINE_PARTIAL_PAYMENT, static function (DefinePartialPaymentEvent $event): void {
			$event->amount = 0.0;
		});

		$customerLanguage = $this->languageOtherThanPrimarySite();
		Craft::$app->language = $customerLanguage;
		$this->partialPayments()->reconcile($cart);

		$this->assertStaffMessage('partialPayment.authorizationReleasedAfterChanges', $customerLanguage, $transaction);
	}

	public function testTheEventAppliedLabelDefaultsToTheCustomerLanguage(): void
	{
		Craft::$app->language = 'de';

		$event = new DefinePartialPaymentEvent();

		self::assertSame(Craft::t('foster-checkout', 'partialPayment.applied', [], 'de'), $event->appliedLabel);
	}

	public function testCompletingAnEmptyCartSaysTheCartIsUnavailable(): void
	{
		$cart = $this->saveCartFor($this->customerWithAddressBook());

		$this->expectException(UserException::class);
		$this->expectExceptionMessage(Craft::t('foster-checkout', 'partialPayment.cartUnavailable'));

		$this->partialPayments()->complete($cart);
	}

	private function assertStaffMessage(string $translationKey, string $customerLanguage, Transaction $transaction): void
	{
		$primaryLanguage = Craft::$app->getSites()->getPrimarySite()->getLanguage();
		$expected = Craft::t('foster-checkout', $translationKey, [], $primaryLanguage);

		self::assertNotSame(Craft::t('foster-checkout', $translationKey, [], $customerLanguage), $expected);
		self::assertSame($expected, $this->savedMessage($transaction));
	}

	private function savedMessage(Transaction $transaction): string
	{
		$record = TransactionRecord::findOne($transaction->id);
		self::assertInstanceOf(TransactionRecord::class, $record);

		return $record->message;
	}

	private function languageOtherThanPrimarySite(): string
	{
		return str_starts_with((string) Craft::$app->getSites()->getPrimarySite()->getLanguage(), 'de') ? 'fr' : 'de';
	}

	/**
	 * @return array{0: Order, 1: NetTerms, 2: Transaction}
	 */
	private function cartWithAuthorization(): array
	{
		$amount = '10.00';
		$gateway = $this->netTermsGateway();
		$customer = $this->customerWithAddressBook();
		$cart = $this->saveCartFor($customer);
		$buyer = $this->buyerFor($customer);
		$cart->setGatewayId((int) $gateway->id);

		$transaction = $this->commerce()->getTransactions()->createTransaction($cart, null, TransactionRecord::TYPE_AUTHORIZE);
		$transaction->amount = (float) $amount;
		$transaction->paymentAmount = Currency::round((float) $amount * $transaction->paymentRate, $transaction->paymentCurrency);

		$entry = NetTermsPlugin::getInstance()->getLedger()->authorize(
			$buyer,
			Amounts::toMoney($amount, $buyer->getAccount()->getCurrency()),
			(int) $cart->id,
			(string) $transaction->hash,
		);

		// Save the record directly, since Commerce completes an order after saving a successful payment
		$record = new TransactionRecord();
		$record->setAttributes($transaction->getAttributes([
			'orderId', 'hash', 'gatewayId', 'type', 'amount', 'currency', 'paymentAmount', 'paymentCurrency', 'paymentRate', 'userId',
		]), false);
		$record->status = TransactionRecord::STATUS_SUCCESS;
		$record->reference = 'net-terms-authorization-' . $entry->id;
		$record->message = 'Authorized in the test';
		$record->response = Json::encode([
			PartialPayments::MARKER => true,
		]);
		$record->save(false);

		$cart->setTransactions(null);
		$savedTransaction = collect($cart->getTransactions())->first(static fn (Transaction $candidate): bool => $candidate->id === $record->id);
		self::assertInstanceOf(Transaction::class, $savedTransaction);

		return [$cart, $gateway, $savedTransaction];
	}

	private function buyerFor(User $customer): Buyer
	{
		$accounts = NetTermsPlugin::getInstance()->getAccounts();
		$store = $this->commerce()->getStores()->getCurrentStore();
		$currency = $store->getCurrency();
		self::assertInstanceOf(MoneyCurrency::class, $currency);

		$account = new Account([
			'storeId' => $store->id,
			'title' => 'Partial payment test',
			'creditLimit' => Amounts::toMoney('100.00', $currency),
		]);
		self::assertTrue($accounts->saveAccount($account), Json::encode($account->getErrors()));

		$buyer = new Buyer([
			'accountId' => $account->id,
			'userId' => $customer->id,
		]);
		self::assertTrue($accounts->saveBuyer($buyer), Json::encode($buyer->getErrors()));

		return $buyer;
	}

	private function netTermsGateway(): NetTerms
	{
		if (! class_exists(NetTermsPlugin::class) || ! NetTermsPlugin::getInstance() instanceof NetTermsPlugin) {
			self::markTestSkipped('Net Terms is not installed on this site.');
		}

		$gateway = $this->commerce()->getGateways()->getAllGateways()->first(static fn (mixed $candidate): bool => $candidate instanceof NetTerms);
		if (! $gateway instanceof NetTerms) {
			self::markTestSkipped('This site has no Net Terms gateway.');
		}

		return $gateway;
	}

	private function partialPayments(): PartialPayments
	{
		return $this->plugin()->getPartialPayments();
	}
}
