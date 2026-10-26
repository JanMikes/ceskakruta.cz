<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Sortiment;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.11: list per category sorted `order DESC`: Název; [A]/[D], [M] (up arrow hidden for first 3 rows), Edit,
 * [X] (hidden for ids 1, 2), [AA] scoped to the category (old app/components/SortimentList/default.latte).
 */
final class SortimentCategoryTest extends SortimentTestCase
{
    /**
     * @param non-empty-string $path
     */
    #[DataProvider('provideUnknownCategoryPaths')]
    public function testUnknownCategoryRedirects(string $path): void
    {
        $this->client->request('GET', $path);

        $this->assertRedirectsTo('/admin/sortiment');
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function provideUnknownCategoryPaths(): iterable
    {
        yield 'list' => ['/admin/sortiment/999999'];
        yield 'add' => ['/admin/sortiment/999999/pridat'];
        yield 'edit' => ['/admin/sortiment/999999/1'];
    }

    public function testListsOnlyItemsOfCategorySortedByOrder(): void
    {
        $categoryId = $this->insertCategory('Čaje');
        $low = $this->insertItem($categoryId, 10, 'Zelený čaj');
        $high = $this->insertItem($categoryId, 30, 'Černý čaj');
        $middle = $this->insertItem($categoryId, 20, 'Bylinný čaj');
        $deleted = $this->insert('sortiment', ['category_id' => $categoryId, 'order' => 25, 'name' => 'Smazaný', 'del_flag' => 1]);

        $crawler = $this->openPage(self::categoryPath($categoryId));

        self::assertSelectorTextContains('h1', 'Čaje');
        self::assertSame([$high, $middle, $low], $this->listedIds($crawler));
        self::assertNotContains($deleted, $this->listedIds($crawler));
        self::assertNotContains(1, $this->listedIds($crawler), 'Items of other categories must not be listed.');
        self::assertSame('Černý čaj', $this->firstCell($crawler, $high));

        self::assertCount(1, $crawler->filter(sprintf('#item-%d a[href="/admin/sortiment/%d/%d"]', $high, $categoryId, $high)), 'Edit link missing.');
        self::assertCount(1, $crawler->filter(sprintf('a[href="/admin/sortiment/%d/pridat"]', $categoryId)), 'Add link missing.');
    }

    public function testFirstThreeRowsCannotMoveUp(): void
    {
        $categoryId = $this->insertCategory();
        $first = $this->insertItem($categoryId, 50);
        $second = $this->insertItem($categoryId, 40);
        $third = $this->insertItem($categoryId, 30);
        $fourth = $this->insertItem($categoryId, 20);
        $last = $this->insertItem($categoryId, 10);

        $crawler = $this->openPage(self::categoryPath($categoryId));

        foreach ([$first, $second, $third] as $id) {
            $this->assertNoGridAction($crawler, 'sortiment', $id, 'move-up');
            $this->gridActionForm($crawler, 'sortiment', $id, 'move-down');
        }

        $this->gridActionForm($crawler, 'sortiment', $fourth, 'move-up');
        $this->gridActionForm($crawler, 'sortiment', $fourth, 'move-down');

        $this->gridActionForm($crawler, 'sortiment', $last, 'move-up');
        $this->assertNoGridAction($crawler, 'sortiment', $last, 'move-down');
    }

    public function testIds1And2CannotBeDeleted(): void
    {
        // Category 1 "Mouky" holds fixture items 1, 2, 3 (tests/database/fixtures.sql)
        $crawler = $this->openPage(self::categoryPath(1));

        $this->assertNoGridAction($crawler, 'sortiment', 1, 'delete');
        $this->assertNoGridAction($crawler, 'sortiment', 2, 'delete');
        $this->gridActionForm($crawler, 'sortiment', 3, 'delete');

        // only delete is restricted, toggling stays available
        $this->gridActionForm($crawler, 'sortiment', 1, 'deactivate');
        $this->gridActionForm($crawler, 'sortiment', 2, 'deactivate');
    }

    public function testActivateActionsAndScopedActivateAll(): void
    {
        $categoryId = $this->insertCategory();
        $active = $this->insertItem($categoryId, 20);
        $inactive = $this->insertItem($categoryId, 10, 'Neaktivní', false);

        $crawler = $this->openPage(self::categoryPath($categoryId));

        $this->gridActionForm($crawler, 'sortiment', $active, 'deactivate');
        $this->assertNoGridAction($crawler, 'sortiment', $active, 'activate');
        $this->gridActionForm($crawler, 'sortiment', $inactive, 'activate');
        $this->assertNoGridAction($crawler, 'sortiment', $inactive, 'deactivate');

        $activateAll = $crawler->filter('form[action="/admin/record/sortiment/activate-all"]');
        self::assertCount(1, $activateAll);
        $scope = $activateAll->filter('input[name="scope"]');
        self::assertCount(1, $scope, 'Activate all must carry the category as scope.');
        self::assertSame((string) $categoryId, $scope->attr('value'));
        self::assertCount(0, $activateAll->filter('button[disabled]'));
    }

    public function testActivateAllDisabledWhenCategoryHasNoInactiveRow(): void
    {
        $categoryId = $this->insertCategory();
        $this->insertItem($categoryId, 10);
        // inactive row in another category must not enable the button (scoped list)
        $this->insertItem(self::FIXTURE_CATEGORY_ID, 5, 'Neaktivní jinde', false);

        $crawler = $this->openPage(self::categoryPath($categoryId));

        $activateAll = $crawler->filter('form[action="/admin/record/sortiment/activate-all"]');
        self::assertCount(1, $activateAll);
        self::assertCount(1, $activateAll->filter('button[disabled]'));
    }
}
