<?php

declare(strict_types=1);

use CeskaKruta\Web\CeskaKrutaKernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new CeskaKrutaKernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
