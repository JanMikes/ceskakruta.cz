<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

/**
 * Index = category management: rows `item-{id}` sorted `order DESC`, link to the items, items count, edit,
 * grid actions (activate/deactivate, move, delete only when the category has no items).
 */
final class SortimentIndexTest extends SortimentTestCase
{
    public function testListsCategories(): void
    {
        $deleted = $this->insert('sortiment_category', ['name' => 'Smazaná kategorie', 'order' => 5, 'del_flag' => 1]);

        $crawler = $this->openPage('/admin/sortiment');

        self::assertSelectorTextSame('h1', 'Doplňkový sortiment');
        $this->assertListedInOrder($crawler, [1, 2, 3, 4]);
        self::assertNotContains($deleted, $this->listedIds($crawler));

        foreach ([1 => 'Mouky', 2 => 'Oleje', 3 => 'Koření', 4 => 'Ostatní'] as $id => $name) {
            $row = $crawler->filter(sprintf('#item-%d', $id));
            self::assertSame($name, trim($row->filter(sprintf('a[href="/admin/sortiment/%d"]', $id))->first()->text()));
            self::assertCount(1, $row->filter(sprintf('a[href="/admin/sortiment/kategorie/%d"]', $id)), 'Edit link');
        }

        // Items count: category 1 has 3 base items, 4 the catalog fixture, 2 is empty
        self::assertStringContainsString('3 položek', $crawler->filter('#item-1')->text());
        self::assertStringContainsString('1 položek', $crawler->filter('#item-4')->text());
        self::assertStringContainsString('0 položek', $crawler->filter('#item-2')->text());

        // Delete only for empty categories
        $this->gridActionForm($crawler, 'sortiment_category', 2, 'delete');
        $this->assertNoGridAction($crawler, 'sortiment_category', 1, 'delete');
        $this->gridActionForm($crawler, 'sortiment_category', 1, 'deactivate');

        self::assertCount(1, $crawler->filter('a[href="/admin/sortiment/kategorie/pridat"]'));
    }

    public function testDeletedItemsDoNotBlockDelete(): void
    {
        $category = $this->insertCategory();
        $this->insert('sortiment', ['category_id' => $category, 'order' => 1, 'name' => 'Smazaná', 'del_flag' => 1]);

        $crawler = $this->openPage('/admin/sortiment');

        $this->gridActionForm($crawler, 'sortiment_category', $category, 'delete');
    }
}
