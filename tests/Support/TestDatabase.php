<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Support;

/**
 * Recreates the test database from tests/database/schema.sql (production schema) + fixtures.sql.
 * The schema is legacy (shared with the old Nette app, no migrations), so plain SQL files are the source.
 */
final class TestDatabase
{
    private const SCHEMA_FILE = __DIR__ . '/../database/schema.sql';
    private const FIXTURES_FILE = __DIR__ . '/../database/fixtures.sql';
    private const FIXTURES_DIR = __DIR__ . '/../database/fixtures';

    public static function recreate(string $databaseUrl): void
    {
        $url = parse_url($databaseUrl);

        if ($url === false || !isset($url['host'], $url['path'])) {
            throw new \RuntimeException('Can not parse DATABASE_URL for tests.');
        }

        $databaseName = ltrim($url['path'], '/');

        // Safety net: this drops the database, so never touch anything that is not explicitly a test database
        if (!str_ends_with($databaseName, '_test') || preg_match('/^[a-z0-9_]+$/', $databaseName) !== 1) {
            throw new \RuntimeException(sprintf('Refusing to recreate database "%s", test database name must end with "_test".', $databaseName));
        }

        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $url['host'], $url['port'] ?? 3306),
            urldecode($url['user'] ?? ''),
            urldecode($url['pass'] ?? ''),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );

        $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $databaseName));
        $pdo->exec(sprintf('CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci', $databaseName));
        $pdo->exec(sprintf('USE `%s`', $databaseName));

        self::executeFile($pdo, self::SCHEMA_FILE);
        self::executeFile($pdo, self::FIXTURES_FILE);

        // Per-section fixtures (each section has its own id range, see PLAN §4.6)
        foreach (glob(self::FIXTURES_DIR . '/*.sql') ?: [] as $file) {
            self::executeFile($pdo, $file);
        }
    }

    /**
     * Statements are executed one by one, so an error in any of them is reported (multi-statement exec would hide it).
     */
    private static function executeFile(\PDO $pdo, string $file): void
    {
        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new \RuntimeException(sprintf('Can not read "%s".', $file));
        }

        $statements = preg_split('/;\s*$/m', $sql);
        assert(is_array($statements));

        foreach ($statements as $statement) {
            $statement = trim((string) preg_replace('/^--.*$/m', '', $statement));

            if ($statement === '') {
                continue;
            }

            try {
                $pdo->exec($statement);
            } catch (\PDOException $exception) {
                throw new \RuntimeException(sprintf("Failed SQL in %s:\n%s\n\n%s", basename($file), mb_substr($statement, 0, 500), $exception->getMessage()), previous: $exception);
            }
        }
    }
}
