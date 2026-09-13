<?php

/**
 * Boot the Craft console application of the site the suite is run from.
 */

spl_autoload_register(static function (string $class): void {
	$prefix = 'fostercommerce\\fostercheckout\\tests\\';

	if (! str_starts_with($class, $prefix)) {
		return;
	}

	$path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

	if (file_exists($path)) {
		require_once $path;
	}
});

$craftBasePath = getenv('CRAFT_BASE_PATH') ?: getcwd();

if (! file_exists($craftBasePath . '/vendor/craftcms/cms/bootstrap/console.php')) {
	fwrite(STDERR, "No Craft installation at {$craftBasePath}.\n\nRun the suites from a site that has the plugin installed, or point CRAFT_BASE_PATH at one:\n  ddev php phpunit.phar -c vendor/fostercommerce/craft-foster-checkout/phpunit.xml\n");
	exit(1);
}

if (file_exists($craftBasePath . '/bootstrap.php')) {
	require $craftBasePath . '/bootstrap.php';
} else {
	// Sites without a bootstrap.php define these inline in their `craft` executable
	define('CRAFT_BASE_PATH', $craftBasePath);
	define('CRAFT_VENDOR_PATH', $craftBasePath . '/vendor');

	require_once CRAFT_VENDOR_PATH . '/autoload.php';

	if (class_exists(Dotenv\Dotenv::class) && file_exists(CRAFT_BASE_PATH . '/.env')) {
		Dotenv\Dotenv::create(CRAFT_BASE_PATH)->load();
	}

	define('CRAFT_ENVIRONMENT', getenv('ENVIRONMENT') ?: 'production');
}

require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';
