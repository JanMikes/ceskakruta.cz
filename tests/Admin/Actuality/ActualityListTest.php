<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Actuality;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

/**
 * PLAN §2.4: list sorted by date DESC, columns Datum (d.m.Y) + Nadpis, [A]/[D], Edit, [X], [AA].
 * Fixtures: tests/database/fixtures/10-newsletter.sql.
 */
final class ActualityListTest extends AdminWebTestCase
{
    public function testListsSortedByDateDesc(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Aktuality');

        // 1001 (2024-09-15), base 1 (2024-05-01), 1000 (2024-03-01), 1003 (2023-12-20, inactive is listed too)
        $this->assertListedInOrder($crawler, [1001, 1, 1000, 1003]);

        // Soft deleted rows are never listed
        self::assertNotContains(1002, $this->listedIds($crawler));
    }

    public function testRowShowsColumnsAndActions(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality');

        self::assertResponseIsSuccessful();

        $row = $crawler->filter('#item-1001');
        self::assertCount(1, $row);
        self::assertStringContainsString('15.09.2024', $row->text());
        self::assertStringContainsString('Podzimní nabídka', $row->text());
        self::assertCount(1, $row->filter('a[href="/admin/aktuality/1001"]'), 'Edit link expected.');

        // Active row: deactivate + delete; inactive row: activate + delete
        $this->gridActionForm($crawler, 'actuality', 1001, 'deactivate');
        $this->gridActionForm($crawler, 'actuality', 1001, 'delete');
        $this->assertNoGridAction($crawler, 'actuality', 1001, 'activate');

        $this->gridActionForm($crawler, 'actuality', 1003, 'activate');
        $this->gridActionForm($crawler, 'actuality', 1003, 'delete');
        $this->assertNoGridAction($crawler, 'actuality', 1003, 'deactivate');

        // Actualities are not orderable
        $this->assertNoGridAction($crawler, 'actuality', 1001, 'move-up');
        $this->assertNoGridAction($crawler, 'actuality', 1001, 'move-down');

        self::assertCount(1, $crawler->filter('a[href="/admin/aktuality/pridat"]'), 'Add link expected.');
    }

    public function testActivateAllDisabledWhenNoneInactive(): void
    {
        $this->loginAsAdmin();

        // Fixture 1003 is inactive → button enabled
        $crawler = $this->client->request('GET', '/admin/aktuality');
        self::assertResponseIsSuccessful();

        $button = $crawler->filter('form[action="/admin/record/actuality/activate-all"] button');
        self::assertCount(1, $button);
        self::assertNull($button->attr('disabled'));

        // No inactive (non-deleted) row → button disabled
        $this->connection()->executeStatement('UPDATE actuality SET active_flag = 1 WHERE del_flag = 0');

        $crawler = $this->client->request('GET', '/admin/aktuality');
        self::assertResponseIsSuccessful();

        $button = $crawler->filter('form[action="/admin/record/actuality/activate-all"] button');
        self::assertCount(1, $button);
        self::assertNotNull($button->attr('disabled'));
    }

    public function testDeactivateFromList(): void
    {
        $this->loginAsAdmin();

        $crawler = $this->client->request('GET', '/admin/aktuality');
        self::assertResponseIsSuccessful();

        $this->client->submit($this->gridActionForm($crawler, 'actuality', 1001, 'deactivate'));

        $this->assertRedirectsTo('/admin/aktuality', 1001);
        self::assertSame(0, $this->fetchInt('SELECT `active_flag` FROM `actuality` WHERE id = :id', ['id' => 1001]));

        $this->client->followRedirect();
        $this->assertFlash('Záznam úspěšně deaktivován');
    }
}
