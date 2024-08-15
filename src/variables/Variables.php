<?php

namespace fostercommerce\craftfostercheckout\variables;

use fostercommerce\craftfostercheckout\FosterCheckout;

class Variables
{
    /**
     * getPath
     */
    public function getPath(string $path): ?string
    {
        $paths = FosterCheckout::getInstance()->checkout->paths();
        return array_key_exists($path, $paths) ? '/' . $paths[$path] : null;
    }
}
