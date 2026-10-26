<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Support;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class IntegrationTestCase extends KernelTestCase
{
    use RestoresExceptionHandler;

    protected function setUp(): void
    {
        parent::setUp();

        HttpMock::reset();
    }

    protected function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function fetchInt(string $sql, array $params = []): int
    {
        $value = $this->connection()->fetchOne($sql, $params);
        self::assertIsNumeric($value);

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function fetchString(string $sql, array $params = []): string
    {
        $value = $this->connection()->fetchOne($sql, $params);
        self::assertIsString($value);

        return $value;
    }
}
