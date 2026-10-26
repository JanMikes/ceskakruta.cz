<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Vendor;

/**
 * PLAN §2.13: list sorted `order DESC`: Název prodejce, Město; [A]/[D], [M], Edit, [X], [AA].
 */
final class VendorListTest extends VendorTestCase
{
    public function testColumns(): void
    {
        $crawler = $this->openPage(self::LIST_PATH);

        self::assertSelectorTextSame('h1', 'Prodejci');
        self::assertSame(['Fixture prodejce', 'Brno'], array_slice($this->rowCells($crawler, self::FIXTURE_ID), 0, 2));
        self::assertSelectorTextContains('thead', 'Název prodejce');
        self::assertSelectorTextContains('thead', 'Město');
        self::assertCount(1, $crawler->filter(sprintf('#item-%d a[href="/admin/prodejci/%d"]', self::FIXTURE_ID, self::FIXTURE_ID)), 'Edit link missing.');
        self::assertCount(1, $crawler->filter('a[href="/admin/prodejci/pridat"]'), 'Add link missing.');
    }

    public function testListSortedByOrderDesc(): void
    {
        $low = $this->insert('vendor', ['order' => 5, 'name' => 'Spodní', 'city' => 'Opava']);
        $high = $this->insert('vendor', ['order' => 30000, 'name' => 'Horní', 'city' => 'Zlín']);
        $deleted = $this->insert('vendor', ['order' => 15, 'name' => 'Smazaný', 'del_flag' => 1]);

        $crawler = $this->openPage(self::LIST_PATH);

        // fixture 2001 order 20010, fixture 1 order 10
        $this->assertListedInOrder($crawler, [$high, self::FIXTURE_ID, 1, $low]);
        self::assertNotContains($deleted, $this->listedIds($crawler));
    }

    public function testGridActions(): void
    {
        $first = $this->insert('vendor', ['order' => 4000000000, 'name' => 'První']);
        $last = $this->insert('vendor', ['order' => 0, 'name' => 'Poslední', 'active_flag' => 0]);

        $crawler = $this->openPage(self::LIST_PATH);

        // first row: no up arrow; last row: no down arrow
        $this->assertNoGridAction($crawler, 'vendor', $first, 'move-up');
        $this->gridActionForm($crawler, 'vendor', $first, 'move-down');
        $this->gridActionForm($crawler, 'vendor', self::FIXTURE_ID, 'move-up');
        $this->gridActionForm($crawler, 'vendor', self::FIXTURE_ID, 'move-down');
        $this->gridActionForm($crawler, 'vendor', $last, 'move-up');
        $this->assertNoGridAction($crawler, 'vendor', $last, 'move-down');

        $this->gridActionForm($crawler, 'vendor', self::FIXTURE_ID, 'deactivate');
        $this->assertNoGridAction($crawler, 'vendor', self::FIXTURE_ID, 'activate');
        $this->gridActionForm($crawler, 'vendor', $last, 'activate');
        $this->assertNoGridAction($crawler, 'vendor', $last, 'deactivate');

        foreach ([$first, self::FIXTURE_ID, $last] as $id) {
            $this->gridActionForm($crawler, 'vendor', $id, 'delete');
        }

        $activateAll = $crawler->filter('form[action="/admin/record/vendor/activate-all"]');
        self::assertCount(1, $activateAll);
        self::assertCount(0, $activateAll->filter('button[disabled]'));
    }
}
