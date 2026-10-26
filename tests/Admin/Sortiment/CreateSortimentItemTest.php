<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

use Symfony\Component\DomCrawler\Crawler;

/**
 * PLAN §2.11 form: `name` (required, max 50 as in old ManageSortimentFormFactory), `text` (WYSIWYG); `category_id` from URL.
 */
final class CreateSortimentItemTest extends SortimentTestCase
{
    public function testCreatesInCategory(): void
    {
        $crawler = $this->openPage('/admin/sortiment/3/pridat');

        self::assertCount(0, $crawler->filter('[name="sortiment[category_id]"]'), 'Category comes from the URL, not from the form.');
        self::assertCount(0, $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text('')) === self::SAVE_AND_CONTINUE,
        ));

        $this->submitAdminForm($crawler, 'sortiment', ['name' => 'Kmín', 'text' => '<p>Celý kmín</p>']);

        $id = $this->lastInsertedId('sortiment');
        self::assertGreaterThan(self::FIXTURE_ID, $id);
        $this->assertRedirectsTo('/admin/sortiment/3', $id);

        $row = $this->fetchRow('sortiment', $id);
        self::assertSame(3, self::intValue($row['category_id']));
        self::assertSame('Kmín', $row['name']);
        self::assertSame('<p>Celý kmín</p>', $row['text']);
        self::assertInsertAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testOrderSetAfterInsert(): void
    {
        $crawler = $this->openPage('/admin/sortiment/3/pridat');
        $this->submitAdminForm($crawler, 'sortiment', ['name' => 'Pepř', 'text' => '']);

        $id = $this->lastInsertedId('sortiment');
        $this->assertRedirectsTo('/admin/sortiment/3', $id);
        self::assertSame($id * 10, self::intValue($this->fetchRow('sortiment', $id)['order']));
    }

    public function testNameRequired(): void
    {
        $before = $this->countRows('sortiment');

        $crawler = $this->openPage('/admin/sortiment/3/pridat');
        $this->submitAdminForm($crawler, 'sortiment', ['name' => '', 'text' => '<p>Bez názvu</p>']);

        $this->assertFormRejected();
        self::assertSame($before, $this->countRows('sortiment'));
    }

    public function testNameMaxLength(): void
    {
        $before = $this->countRows('sortiment');

        $crawler = $this->openPage('/admin/sortiment/3/pridat');
        $this->submitAdminForm($crawler, 'sortiment', ['name' => str_repeat('x', 51), 'text' => '']);

        $this->assertFormRejected();
        self::assertSame($before, $this->countRows('sortiment'));
    }
}
