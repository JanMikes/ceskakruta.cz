<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->mailer()->transport('main', env('MAILER_DSN'));

    $framework->mailer()->transport('orders', env('MAILER_ORDERS_DSN'));
};
