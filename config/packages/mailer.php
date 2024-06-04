<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $mailerConfig = $framework->mailer();
    $mailerConfig
        ->dsn(env('MAILER_DSN'));

    $mailerConfig
        ->envelope()
        ->sender('info@ceskakruta.cz');
};
