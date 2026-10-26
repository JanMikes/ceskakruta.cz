<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

/**
 * PLAN §2.6: sorted `year ASC, week_number ASC, id DESC`; columns Týden "W/YYYY (d.m. - d.m. YYYY)", Farma Doubrava, Farma Rychvald, Stáří v týdnech.
 */
final class CalendarDeadListTest extends CalendarListTestCase
{
    use CalendarDeadSection;

    protected static function heading(): string
    {
        return 'Rozkrmená krůťata';
    }

    public function testListSortingAndColumns(): void
    {
        $nextYear = $this->insertCalendarRow(1, 2030, ['farma_doubrava' => 0, 'farma_rychvald' => 1, 'week_age' => 8]);
        $lateWeek = $this->insertCalendarRow(52, 2029);
        $sameWeekOlder = $this->insertCalendarRow(5, 2029, ['farma_doubrava' => 1, 'farma_rychvald' => 0, 'week_age' => 6]);
        $sameWeekNewer = $this->insertCalendarRow(5, 2029);

        $crawler = $this->openPage(self::path());

        // week 20/2025 fixture row first, then year ASC, week ASC, id DESC
        $this->assertListedInOrder($crawler, [self::FIXTURE_ID, $sameWeekNewer, $sameWeekOlder, $lateWeek, $nextYear]);

        // Columns: Týden, Farma Doubrava, Farma Rychvald, Stáří v týdnech
        self::assertSame(['5/2029 (29.01. - 04.02. 2029)', 'Ano', 'Ne', '6'], array_slice($this->rowCells($crawler, $sameWeekOlder), 0, 4));
        // Week 1 starting in the previous December
        self::assertSame(['1/2030 (31.12. - 06.01. 2030)', 'Ne', 'Ano', '8'], array_slice($this->rowCells($crawler, $nextYear), 0, 4));
        self::assertSame(['52/2029 (24.12. - 30.12. 2029)', 'Ne', 'Ne', ''], array_slice($this->rowCells($crawler, $lateWeek), 0, 4));

        foreach (['Týden', 'Farma Doubrava', 'Farma Rychvald', 'Stáří v týdnech'] as $column) {
            self::assertSelectorTextContains('thead', $column);
        }
    }

    protected function insertCalendarRow(int $week, int $year, array $row = []): int
    {
        return $this->insert('calendar_dead', $row + [
            'week_number' => $week,
            'year' => $year,
            'farma_doubrava' => 0,
            'farma_rychvald' => 0,
            'week_age' => null,
        ]);
    }
}
