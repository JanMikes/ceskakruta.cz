<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\Cart\SessionCartStorage;
use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

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

    $services->set(PdoSessionHandler::class)
        ->args([
            env('DATABASE_URL'),
        ]);

    $services->set(PsrLogMessageProcessor::class)
        ->tag('monolog.processor');

    // Controllers
    $services->load('CeskaKruta\\Web\\Controller\\', __DIR__ . '/../src/Controller/{*Controller.php}');

    // Message handlers
    $services->load('CeskaKruta\\Web\\MessageHandler\\', __DIR__ . '/../src/MessageHandler/**/{*.php}');

    // Services
    $services->load('CeskaKruta\\Web\\Services\\', __DIR__ . '/../src/Services/**/{*.php}');
    $services->load('CeskaKruta\\Web\\Query\\', __DIR__ . '/../src/Query/**/{*.php}');

    $services->alias(CartStorage::class, SessionCartStorage::class);
};
