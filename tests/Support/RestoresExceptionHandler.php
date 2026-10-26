<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Support;

/**
 * FrameworkBundle::boot() registers Symfony's ErrorHandler on every kernel boot and Symfony 7.1 test cases don't restore it,
 * which PHPUnit 11 reports as risky ("did not remove its own exception handlers").
 */
trait RestoresExceptionHandler
{
    protected function tearDown(): void
    {
        parent::tearDown();

        restore_exception_handler();
    }
}
