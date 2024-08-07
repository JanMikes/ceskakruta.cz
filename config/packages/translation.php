<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $framework): void {
    $framework
        ->defaultLocale('cs')
        ->enabledLocales(['cs'])
        ->setContentLanguageFromLocale(false)
        ->setLocaleFromAcceptLanguage(false);
};
