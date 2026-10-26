<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Infrastructure;

use CeskaKruta\Web\Services\Security\PasswordHasher;
use CeskaKruta\Web\Tests\Support\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Depends;

final class TestInfrastructureTest extends IntegrationTestCase
{
    public function testUsesSeparateTestDatabaseOnProductionServerVersion(): void
    {
        $connection = $this->connection();

        self::assertSame('ceskakruta_test', $connection->getDatabase());
        self::assertStringStartsWith('10.11.', $this->fetchString('SELECT VERSION()'));
        self::assertStringContainsString('STRICT_TRANS_TABLES', $this->fetchString('SELECT @@SESSION.sql_mode'));
    }

    public function testSchemaIsProductionSchema(): void
    {
        $connection = $this->connection();

        self::assertSame(31, $this->fetchInt('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'));

        // Production specific: product.unit is NOT NULL without default (local dev used to have default 'kg')
        $unitDefault = $connection->fetchAssociative(
            "SELECT is_nullable, column_default FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product' AND column_name = 'unit'",
        );
        self::assertSame(['is_nullable' => 'NO', 'column_default' => null], $unitDefault);
    }

    public function testFixturePasswordsMatchApplicationHasher(): void
    {
        $hash = $this->connection()->fetchOne("SELECT password FROM `user` WHERE email = 'admin@example.test' AND is_admin = 1");
        self::assertIsString($hash);

        $hasher = self::getContainer()->get(PasswordHasher::class);
        self::assertTrue($hasher->verify($hash, 'admin-password'));
        self::assertFalse($hasher->verify($hash, 'wrong-password'));
    }

    public function testWriteInsideTest(): void
    {
        $this->connection()->insert('vendor', [
            'id' => 9999,
            'name' => 'Rolled back vendor',
            'ins_dt' => '2024-01-01 00:00:00',
        ]);

        self::assertSame(1, $this->fetchInt('SELECT COUNT(*) FROM vendor WHERE id = 9999'));
    }

    #[Depends('testWriteInsideTest')]
    public function testWritesFromPreviousTestAreRolledBack(): void
    {
        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM vendor WHERE id = 9999'));
    }
}
