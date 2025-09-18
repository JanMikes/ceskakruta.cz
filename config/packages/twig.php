<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\ContentService;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (\Symfony\Config\TwigConfig $twig): void {
    $twig->formThemes(['bootstrap_5_layout.html.twig']);

    $twig->global('cart')->value(
        service(CartService::class)
    );

    $twig->global('content')->value(
        service(ContentService::class)
    );

    $twig->date([
        'timezone' => 'Europe/Prague',
    ]);

    $twig->path('%kernel.project_dir%/public/img', 'images');
    $twig->path('%kernel.project_dir%/public/css', 'styles');
};
