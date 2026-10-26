<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\GoLive;

use PHPUnit\Framework\TestCase;

/**
 * PLAN §3.9: user uploads live in `public/upload/` on the FTP hosting — a deploy must never touch them
 * and they never belong to git.
 */
final class DeployConfigTest extends TestCase
{
    public function testUploadsDirIsExcludedFromFtpDeployAndGit(): void
    {
        $root = dirname(__DIR__, 3);

        $release = file_get_contents($root . '/.github/workflows/release.yml');
        self::assertIsString($release);
        self::assertMatchesRegularExpression('~^\s*\*\*/public/upload/\*\*\s*$~m', $release, 'FTP-Deploy-Action exclude must contain **/public/upload/**');

        $gitignore = file_get_contents($root . '/.gitignore');
        self::assertIsString($gitignore);
        self::assertMatchesRegularExpression('~^/public/upload/?\s*$~m', $gitignore, '.gitignore must contain /public/upload/');
    }
}
