<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (ContainerConfigurator $containerConfigurator, FrameworkConfig $framework): void {
    $containerConfigurator->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'http_method_override' => false,
        'csrf_protection' => true,
        'session' => [
            'cookie_secure' => 'auto',
            'cookie_samesite' => 'lax',
            'storage_factory_id' => 'session.storage.factory.native',
        ],
        'php_errors' => [
            'log' => true,
        ],
        'trusted_headers' => ['x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'x-forwarded-port', 'x-forwarded-prefix'],
        'trusted_proxies' => '127.0.0.1,REMOTE_ADDR',
        'fragments' => [
            'path' => '_fragments'
        ],
    ]);

    $framework->httpClient()->scopedClient('coolbalik.client')
        ->baseUri('https://api.boxxi-logistics.cz')
        ->header('x-api-key', env('COOLBALIK_TOKEN'))
        ->header('accept', 'application/json');
};
