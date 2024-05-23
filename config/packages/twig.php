<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\Cart\CartService;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (\Symfony\Config\TwigConfig $twig): void {
    $twig->formThemes(['bootstrap_5_layout.html.twig']);

    $twig->global('cart')->value(
        service(CartService::class)
    );

    $twig->date([
        'timezone' => 'Europe/Prague',
    ]);

};
