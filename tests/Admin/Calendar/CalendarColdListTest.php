<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

/**
 * PLAN §2.7: sorted `year ASC, week_number ASC, product_tp_id DESC, id DESC`; columns Týden, Produkt (lst_product_tp.name), Váha "X až Y".
 */
final class CalendarColdListTest extends CalendarListTestCase
{
    use CalendarColdSection;

    protected static function heading(): string
    {
        return 'Chlazené krůty';
    }

    public function testListSortingAndColumns(): void
    {
        $nextYear = $this->insertCalendarRow(1, 2030, ['product_tp_id' => 1]);
        $lateWeek = $this->insertCalendarRow(52, 2029, ['product_tp_id' => 2]);
        $turkeyOlder = $this->insertCalendarRow(5, 2029, ['product_tp_id' => 1, 'weight_from' => 6, 'weight_to' => 8]);
        $turkeyNewer = $this->insertCalendarRow(5, 2029, ['product_tp_id' => 1]);
        $tom = $this->insertCalendarRow(5, 2029, ['product_tp_id' => 2, 'weight_from' => 10, 'weight_to' => 12]);

        $crawler = $this->openPage(self::path());

        // same week: product_tp_id DESC (Krocan 2 before Krůta 1), then id DESC
        $this->assertListedInOrder($crawler, [self::FIXTURE_ID, $tom, $turkeyNewer, $turkeyOlder, $lateWeek, $nextYear]);

        // Columns: Týden, Produkt, Váha
        self::assertSame(['5/2029 (29.01. - 04.02. 2029)', 'Krůta', '6 až 8'], array_slice($this->rowCells($crawler, $turkeyOlder), 0, 3));
        self::assertSame(['5/2029 (29.01. - 04.02. 2029)', 'Krocan', '10 až 12'], array_slice($this->rowCells($crawler, $tom), 0, 3));
        self::assertSame('1/2030 (31.12. - 06.01. 2030)', $this->firstCell($crawler, $nextYear));

        foreach (['Týden', 'Produkt', 'Váha'] as $column) {
            self::assertSelectorTextContains('thead', $column);
        }
    }

    protected function insertCalendarRow(int $week, int $year, array $row = []): int
    {
        return $this->insert('calendar_cold', $row + [
            'week_number' => $week,
            'year' => $year,
            'product_tp_id' => 1,
            'weight_from' => 5,
            'weight_to' => 7,
        ]);
    }
}
