<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

/**
 * Calendar lists: [A]/[D], Edit, [X], [AA]; not orderable → no move forms (PLAN §2.6–2.8, §4.6 "Lists").
 */
abstract class CalendarListTestCase extends CalendarTestCase
{
    /**
     * Inserts a row of the table with sensible defaults for the given week.
     *
     * @param array<string, scalar|null> $row
     */
    abstract protected function insertCalendarRow(int $week, int $year, array $row = []): int;

    public function testPageHeading(): void
    {
        $this->openPage(static::path());

        self::assertSelectorTextSame('h1', static::heading());
    }

    abstract protected static function heading(): string;

    public function testRendersGridActions(): void
    {
        $active = $this->insertCalendarRow(14, 2031);
        $inactive = $this->insertCalendarRow(15, 2031, ['active_flag' => 0]);

        $crawler = $this->openPage(static::path());
        $table = static::table();

        $this->gridActionForm($crawler, $table, $active, 'deactivate');
        $this->assertNoGridAction($crawler, $table, $active, 'activate');
        $this->gridActionForm($crawler, $table, $inactive, 'activate');
        $this->assertNoGridAction($crawler, $table, $inactive, 'deactivate');

        foreach ([$active, $inactive] as $id) {
            $this->gridActionForm($crawler, $table, $id, 'delete');
            $this->assertNoGridAction($crawler, $table, $id, 'move-up');
            $this->assertNoGridAction($crawler, $table, $id, 'move-down');
            self::assertCount(1, $crawler->filter(sprintf('#item-%d a[href="%s/%d"]', $id, static::path(), $id)), 'Edit link missing.');
        }

        $activateAll = $crawler->filter(sprintf('form[action="/admin/record/%s/activate-all"]', $table));
        self::assertCount(1, $activateAll);
        self::assertCount(0, $activateAll->filter('button[disabled]'), 'Activate all must be enabled when there is an inactive row.');

        self::assertCount(1, $crawler->filter(sprintf('a[href="%s/pridat"]', static::path())), 'Add link missing.');
    }

    public function testDeletedRowsAreNotListed(): void
    {
        $deleted = $this->insertCalendarRow(16, 2031, ['del_flag' => 1]);

        $crawler = $this->openPage(static::path());

        self::assertNotContains($deleted, $this->listedIds($crawler));
        self::assertContains(self::FIXTURE_ID, $this->listedIds($crawler));
    }
}
