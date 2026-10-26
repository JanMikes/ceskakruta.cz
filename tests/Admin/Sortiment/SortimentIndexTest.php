<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

use Symfony\Component\DomCrawler\Crawler;

/**
 * PLAN §2.11: index = one button per category linking to its list; no category management.
 */
final class SortimentIndexTest extends SortimentTestCase
{
    public function testListsCategories(): void
    {
        $deleted = $this->insert('sortiment_category', ['name' => 'Smazaná kategorie', 'order' => 5, 'del_flag' => 1]);

        $crawler = $this->openPage('/admin/sortiment');

        self::assertSelectorTextSame('h1', 'Doplňkový sortiment');

        // (the navbar dropdown links the categories too, so look for a link with the category name anywhere)
        foreach ([1 => 'Mouky', 2 => 'Oleje', 3 => 'Koření', 4 => 'Ostatní'] as $id => $name) {
            $links = $crawler->filter(sprintf('a[href="/admin/sortiment/%d"]', $id))->reduce(
                static fn (Crawler $link): bool => trim($link->text('')) === $name,
            );
            self::assertGreaterThanOrEqual(1, $links->count(), sprintf('Link "%s" to category %d missing.', $name, $id));
        }

        self::assertCount(0, $crawler->filter(sprintf('a[href="/admin/sortiment/%d"]', $deleted)));
        self::assertCount(0, $crawler->filter('form[action^="/admin/record/sortiment_category"]'), 'No category management.');
    }
}
