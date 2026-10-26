<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Content;

/**
 * PLAN §2.12: list sorted `order DESC`: Sekce (name), Edit. No create ("Přidat" commented out in old Content.default.latte),
 * no grid actions (old ContentsList template has none).
 */
final class ContentListTest extends ContentTestCase
{
    public function testListSortedByOrder(): void
    {
        $low = $this->insert('content', ['order' => 5, 'code' => 'low_section', 'name' => 'Spodní sekce', 'text' => '<p>x</p>']);
        $high = $this->insert('content', ['order' => 20020, 'code' => 'high_section', 'name' => 'Horní sekce', 'text' => '<p>x</p>']);
        $deleted = $this->insert('content', ['order' => 15, 'code' => 'deleted_section', 'name' => 'Smazaná sekce', 'del_flag' => 1]);

        $crawler = $this->openPage(self::LIST_PATH);

        self::assertSelectorTextSame('h1', 'Texty');
        // fixture 1 has order 10, fixture 2001 has order 20010
        $this->assertListedInOrder($crawler, [$high, self::FIXTURE_ID, 1, $low]);
        self::assertNotContains($deleted, $this->listedIds($crawler));

        self::assertSame('Editovatelná sekce', $this->firstCell($crawler, self::FIXTURE_ID));
        self::assertSelectorTextContains('thead', 'Sekce');
        self::assertCount(1, $crawler->filter(sprintf('#item-%d a[href="/admin/texty/%d"]', self::FIXTURE_ID, self::FIXTURE_ID)), 'Edit link missing.');
    }

    public function testHasNoCreateAndNoGridActions(): void
    {
        $crawler = $this->openPage(self::LIST_PATH);

        self::assertContains(self::FIXTURE_ID, $this->listedIds($crawler));
        self::assertCount(0, $crawler->filter('a[href="/admin/texty/pridat"]'));
        self::assertCount(0, $crawler->filter('form[action^="/admin/record/content"]'));
    }
}
