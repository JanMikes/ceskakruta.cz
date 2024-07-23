<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $mailerConfig = $framework->mailer();

    $mailerConfig->transport('main', env('MAILER_DSN'))
        ->envelope()
        ->sender('info@ceskakruta.cz');

    $mailerConfig->transport('orders', env('MAILER_ORDERS_DSN'))
        ->envelope()
        ->sender('objednavky@ceskakruta.cz');

};
