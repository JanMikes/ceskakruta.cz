<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Security\PasswordHasher;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Value\User;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $securityConfig): void {
    $securityConfig->provider('dbal_provider')
        ->id(UserProvider::class);

    $securityConfig->firewall('dev')
        ->pattern('^/(_(profiler|wdt)|css|img|js|build|fonts|vendor)/')
        ->security(false);

    $mainFirewall = $securityConfig->firewall('main')
        ->pattern('^/')
        ->provider('dbal_provider');

    $mainFirewall->formLogin()
        ->loginPath('login')
        ->checkPath('login')
        ->defaultTargetPath('/uzivatel/muj-ucet');

    $mainFirewall->logout()
        ->path('logout')
        ->target('/');

    $securityConfig->passwordHasher(User::class)
        ->id(PasswordHasher::class);

    $securityConfig->accessControl()
        ->path('^/(uzivatel)')
        ->roles([AuthenticatedVoter::IS_AUTHENTICATED_FULLY]);

    $securityConfig->accessControl()
        ->path('^(/|/cron)')
        ->roles([AuthenticatedVoter::PUBLIC_ACCESS]);
};
