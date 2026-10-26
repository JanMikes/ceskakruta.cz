<?php

declare(strict_types=1);

use CeskaKruta\Web\Tests\Support\HttpMock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'test' => true,
        'session' => [
            'storage_factory_id' => 'session.storage.factory.mock_file',
        ],
        // No real HTTP calls from tests (Boxxi, Pohoda, ...)
        'http_client' => [
            'mock_response_factory' => HttpMock::class,
        ],
    ]);
};
