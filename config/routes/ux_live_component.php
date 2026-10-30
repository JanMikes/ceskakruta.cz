<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('@LiveComponentBundle/config/routes.php')
        ->prefix('/_components');

    // Admin live components (`#[AsLiveComponent(route: 'admin_live_component')]`) re-render under /admin = admin firewall + ROLE_ADMIN
    $routingConfigurator->add('admin_live_component', '/admin/_components/{_live_component}/{_live_action}')
        ->defaults(['_live_action' => 'get']);
};
