<?php

declare(strict_types=1);

use CeskaKruta\Web\Services\Security\AdminUserChecker;
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

    // Must be declared before `main`, whose pattern matches everything (docs/admin/PLAN.md §3.2)
    $adminFirewall = $securityConfig->firewall('admin')
        ->pattern('^/admin')
        ->provider('dbal_provider')
        ->userChecker(AdminUserChecker::class);

    $adminFirewall->formLogin()
        ->loginPath('admin_login')
        ->checkPath('admin_login')
        ->defaultTargetPath('/admin')
        ->enableCsrf(true);

    // Checkbox "Trvalé přihlášení" of the old admin, 14 days
    $adminFirewall->rememberMe()
        ->secret('%kernel.secret%')
        ->name('ADMIN_REMEMBERME')
        ->lifetime(14 * 86400)
        ->path('/admin');

    $adminFirewall->logout()
        ->path('admin_logout')
        ->target('admin_login');

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

    // First match wins — the admin rules must come before the catch-all public rule below
    $securityConfig->accessControl()
        ->path('^/admin/login$')
        ->roles([AuthenticatedVoter::PUBLIC_ACCESS]);

    $securityConfig->accessControl()
        ->path('^/admin')
        ->roles(['ROLE_ADMIN']);

    $securityConfig->accessControl()
        ->path('^/(uzivatel)')
        ->roles([AuthenticatedVoter::IS_AUTHENTICATED_FULLY]);

    $securityConfig->accessControl()
        ->path('^(/|/cron)')
        ->roles([AuthenticatedVoter::PUBLIC_ACCESS]);
};
