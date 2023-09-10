<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\Cart\InMemoryCartStorage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function(ContainerConfigurator $configurator): void
{
    $services = $configurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->public();

    $services->alias(CartStorage::class, InMemoryCartStorage::class);

    // Data fixtures
    $services->load('CeskaKruta\\Web\\Tests\\DataFixtures\\', __DIR__ . '/../tests/DataFixtures/{*.php}');
};
