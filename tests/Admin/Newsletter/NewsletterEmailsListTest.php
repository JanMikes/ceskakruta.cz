<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Newsletter;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

/**
 * PLAN §2.14: subscribers list sorted by email ASC, columns Email + Přidáno (d.m.Y H:i:s), only [X] delete.
 * Fixtures: tests/database/fixtures/10-newsletter.sql.
 */
final class NewsletterEmailsListTest extends AdminWebTestCase
{
    public function testListsSortedByEmail(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/emaily');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Emaily');

        // adam, inactive (inactive rows are listed), marek, base subscriber@, zuzana
        $this->assertListedInOrder($crawler, [1001, 1003, 1002, 1, 1000]);
        self::assertNotContains(1004, $this->listedIds($crawler), 'Deleted subscribers are not listed.');

        $row = $crawler->filter('#item-1001');
        self::assertCount(1, $row);
        self::assertStringContainsString('adam@example.test', $row->text());
        self::assertStringContainsString('05.03.2024 12:34:56', $row->text());

        // Only delete, no activate/deactivate/move
        $this->gridActionForm($crawler, 'newsletter', 1001, 'delete');
        $this->assertNoGridAction($crawler, 'newsletter', 1001, 'activate');
        $this->assertNoGridAction($crawler, 'newsletter', 1001, 'deactivate');
        $this->assertNoGridAction($crawler, 'newsletter', 1001, 'move-up');
        self::assertCount(0, $crawler->filter('form[action="/admin/record/newsletter/activate-all"]'));
    }

    public function testPaginatesFiftyPerPage(): void
    {
        // 55 extra subscribers sorted after the fixtures → 60 rows = 2 pages
        for ($i = 1; $i <= 55; $i++) {
            $this->insert('newsletter', [
                'email' => sprintf('zz-paging-%02d@example.test', $i),
                'ins_dt' => '2024-01-01 00:00:00',
                'active_flag' => 1,
                'del_flag' => 0,
            ]);
        }

        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/emaily');
        self::assertResponseIsSuccessful();
        self::assertCount(50, $this->listedIds($crawler));
        self::assertStringContainsString('Celkem 60', $crawler->filter('body')->text());
        $nextPage = $crawler->filter('nav[aria-label="Stránkování"] a[href="/admin/emaily?page=2"]');
        self::assertContains('2', $nextPage->each(static fn ($link): string => trim($link->text())));

        $crawler = $this->client->request('GET', '/admin/emaily?page=2');
        self::assertResponseIsSuccessful();
        self::assertCount(10, $this->listedIds($crawler));
        self::assertStringContainsString('zz-paging-55@example.test', $crawler->filter('body')->text());

        // Deleting returns to the same page
        $id = $this->listedIds($crawler)[0];
        $this->client->submit($this->gridActionForm($crawler, 'newsletter', $id, 'delete'));
        $this->assertRedirectsTo('/admin/emaily?page=2', $id);

        // Out of range clamps to the last page
        $crawler = $this->client->request('GET', '/admin/emaily?page=99');
        self::assertResponseIsSuccessful();
        self::assertCount(9, $this->listedIds($crawler));
    }

    public function testDelete(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/emaily');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->gridActionForm($crawler, 'newsletter', 1001, 'delete'));

        $this->assertRedirectsTo('/admin/emaily', 1001);

        $row = $this->fetchRow('newsletter', 1001);
        self::assertEquals(1, $row['del_flag']);
        self::assertSame('admin', $row['upd_process']);

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam úspěšně smazán');
        self::assertNotContains(1001, $this->listedIds($crawler));
    }
}
