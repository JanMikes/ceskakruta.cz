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
