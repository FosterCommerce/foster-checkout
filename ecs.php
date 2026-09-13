<?php

declare(strict_types=1);

use fostercommerce\ecs\ECSConfig;

return ECSConfig::configure()
	->withPaths([
		__DIR__ . '/src',
		__DIR__ . '/tests',
		__FILE__,
	]);
