<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;

/**
 * Every admin page loads for an admin — the top level TODO list of the whole migration.
 */
final class AdminPagesSmokeTest extends AdminWebTestCase
{
    #[DataProviderExternal(AdminPages::class, 'htmlPagesProvider')]
    public function testPageLoads(string $url, null|string $expectedHeading): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('h1'), 'Every admin page has exactly one <h1>.');
        self::assertNotSame('', trim($crawler->filter('h1')->text()));

        if ($expectedHeading !== null) {
            self::assertSelectorTextContains('h1', $expectedHeading);
        }
    }

    public function testOrdersExportDownloadsCsv(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/objednavky/export.csv');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
    }
}
