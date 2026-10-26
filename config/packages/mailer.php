<?php

declare(strict_types=1);

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework->mailer()->transport('main', env('MAILER_DSN'));

    $framework->mailer()->transport('orders', env('MAILER_ORDERS_DSN'));

    // Newsletter (X-Transport: newsletter), production phpmail://default = PHP mail() like the old app (PLAN §3.8),
    // the phpmail:// scheme comes from PhpMailTransportFactory (tagged mailer.transport_factory by its attribute)
    $framework->mailer()->transport('newsletter', env('MAILER_NEWSLETTER_DSN'));
};
