<?php

namespace fostercommerce\fostercheckout\tests\unit;

use fostercommerce\fostercheckout\models\PathConfig;
use fostercommerce\fostercheckout\tests\Support\CheckoutTestCase;

final class PathConfigTest extends CheckoutTestCase
{
	public function testDefaultsAreTheUnprefixedCartAndCheckout(): void
	{
		$paths = new PathConfig();

		self::assertSame('cart', $paths->cart);
		self::assertSame('checkout', $paths->checkout);
		self::assertSame('/', $paths->cancel);
		self::assertSame('/', $paths->account);
		self::assertTrue($paths->useCartTemplate);
	}

	/**
	 * Templates join these onto a site url, so a kept slash would double it.
	 */
	public function testSlashesAreTrimmedFromBothEnds(): void
	{
		$paths = new PathConfig([
			'cart' => '/shop/cart/',
			'checkout' => '/shop/checkout',
		]);

		self::assertSame('shop/cart', $paths->cart);
		self::assertSame('shop/checkout', $paths->checkout);
	}

	public function testCancelAndAccountKeepTheirSlashes(): void
	{
		$paths = new PathConfig([
			'cancel' => '/shop/',
			'account' => '/account/',
		]);

		self::assertSame('/shop/', $paths->cancel);
		self::assertSame('/account/', $paths->account);
	}
}
