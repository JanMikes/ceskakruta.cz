<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\Security\PasswordHasher;
use h4kuna\Ares\Ares;
use h4kuna\Ares\AresFactory;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function(ContainerConfigurator $configurator): void
{
    $parameters = $configurator->parameters();

    # https://symfony.com/doc/current/performance.html#dump-the-service-container-into-a-single-file
    $parameters->set('container.dumper.inline_factories', true);

    $services = $configurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->public();

    $services->set(PsrLogMessageProcessor::class)
        ->tag('monolog.processor');

    // Controllers
    $services->load('CeskaKruta\\Web\\Controller\\', __DIR__ . '/../src/Controller/{*Controller.php}');

    // Message handlers
    $services->load('CeskaKruta\\Web\\MessageHandler\\', __DIR__ . '/../src/MessageHandler/**/{*.php}');

    // Services
    $services->load('CeskaKruta\\Web\\Services\\', __DIR__ . '/../src/Services/**/{*.php}');
    $services->load('CeskaKruta\\Web\\Query\\', __DIR__ . '/../src/Query/**/{*.php}');
    $services->load('CeskaKruta\\Web\\Repository\\', __DIR__ . '/../src/Repository/**/{*.php}');

    // Components
    $services->load('CeskaKruta\\Web\\Components\\', __DIR__ . '/../src/Components/**/{*.php}');

    $services->set(CartStorage::class);

    $services->set(PasswordHasher::class)
        ->args([
            '$leadingSalt' => env('SECURITY_SALT_1'),
            '$trailingSalt' => env('SECURITY_SALT_2'),
        ]);

    // Register AresFactory as a service, injecting the PSR HTTP client
    $services->set(AresFactory::class)
        ->args([ service(ClientInterface::class) ]);

    $services->set(Ares::class)
        ->factory([service(AresFactory::class), 'create']);
};
