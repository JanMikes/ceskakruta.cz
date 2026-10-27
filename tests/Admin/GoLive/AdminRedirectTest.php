<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\GoLive;

use PHPUnit\Framework\TestCase;

/**
 * PLAN §1.3.2 / §3.10: at admin go-live the `/admin*` → old.ceskakruta.cz rule is removed from public/.htaccess,
 * so `/admin` is served by this app.
 */
final class AdminRedirectTest extends TestCase
{
    public function testHtaccessNoLongerRedirectsAdminToOldDomain(): void
    {
        $htaccess = file_get_contents(dirname(__DIR__, 3) . '/public/.htaccess');
        self::assertIsString($htaccess);

        self::assertStringNotContainsString('old.ceskakruta.cz/admin', $htaccess);

        foreach (preg_split('/\R/', $htaccess) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            self::assertDoesNotMatchRegularExpression('~^RewriteRule\s+\S*admin~i', $line, 'No rewrite rule for /admin may remain.');
        }
    }

    public function testMissingUploadsFallBackToOldDomain(): void
    {
        $htaccess = file_get_contents(dirname(__DIR__, 3) . '/public/.htaccess');
        self::assertIsString($htaccess);

        // Recipe photos are copied over by hand; until then the shop must not show broken images
        self::assertStringContainsString('RewriteRule ^upload/(.+)$ https://old.ceskakruta.cz/upload/$1 [R=302,L]', $htaccess);
    }

    public function testFrontControllerRuleIsKept(): void
    {
        $htaccess = file_get_contents(dirname(__DIR__, 3) . '/public/.htaccess');
        self::assertIsString($htaccess);

        // Guard for the go-live edit: only the admin rule goes away
        self::assertStringContainsString('index.php [L]', $htaccess);
        self::assertStringContainsString('RewriteEngine On', $htaccess);
    }
}
