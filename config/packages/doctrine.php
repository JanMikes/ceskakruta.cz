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
            'use_savepoints' => true,
            'types' => [
                'uuid' => UuidType::class,
                AddressDoctrineType::NAME => AddressDoctrineType::class,
                PriceDoctrineType::NAME => PriceDoctrineType::class,
            ],
        ],
    ]);
};
