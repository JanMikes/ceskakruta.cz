<?php

declare(strict_types=1);

use CeskaKruta\Web\Doctrine\AddressDoctrineType;
use CeskaKruta\Web\Doctrine\PriceDoctrineType;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
            'types' => [
                'uuid' => UuidType::class,
                AddressDoctrineType::NAME => AddressDoctrineType::class,
                PriceDoctrineType::NAME => PriceDoctrineType::class,
            ],
        ],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => true,
            'mappings' => [
                'CeskaKruta' => [
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'CeskaKruta\\Web\\Entity',
                ],
            ],
        ],
    ]);
};
