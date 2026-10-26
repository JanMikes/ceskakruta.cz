<?php

declare(strict_types=1);

use CeskaKruta\Web\Tests\Support\HttpMock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    // Used as framework.http_client.mock_response_factory (config/packages/test/framework.php)
    $services->set(HttpMock::class);
};
