<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

/**
 * PLAN §2.8: like cold (same sorting) + Farma Doubrava / Farma Rychvald columns.
 * Column order from old app/components/CalendarAliveList/default.latte: Týden, Produkt, Farma Doubrava, Farma Rychvald, Váha.
 */
final class CalendarAliveListTest extends CalendarListTestCase
{
    use CalendarAliveSection;

    protected static function heading(): string
    {
        return 'Živé krůty';
    }

    public function testListSortingAndColumns(): void
    {
        $nextYear = $this->insertCalendarRow(1, 2030, ['product_tp_id' => 1]);
        $lateWeek = $this->insertCalendarRow(52, 2029, ['product_tp_id' => 2]);
        $turkeyOlder = $this->insertCalendarRow(5, 2029, ['product_tp_id' => 1, 'farma_doubrava' => 1, 'farma_rychvald' => 0, 'weight_from' => 6, 'weight_to' => 8]);
        $turkeyNewer = $this->insertCalendarRow(5, 2029, ['product_tp_id' => 1]);
        $tom = $this->insertCalendarRow(5, 2029, ['product_tp_id' => 2, 'farma_doubrava' => 0, 'farma_rychvald' => 1, 'weight_from' => 10, 'weight_to' => 12]);

        $crawler = $this->openPage(self::path());

        $this->assertListedInOrder($crawler, [self::FIXTURE_ID, $tom, $turkeyNewer, $turkeyOlder, $lateWeek, $nextYear]);

        self::assertSame(['5/2029 (29.01. - 04.02. 2029)', 'Krůta', 'Ano', 'Ne', '6 až 8'], array_slice($this->rowCells($crawler, $turkeyOlder), 0, 5));
        self::assertSame(['5/2029 (29.01. - 04.02. 2029)', 'Krocan', 'Ne', 'Ano', '10 až 12'], array_slice($this->rowCells($crawler, $tom), 0, 5));
        self::assertSame('1/2030 (31.12. - 06.01. 2030)', $this->firstCell($crawler, $nextYear));

        foreach (['Týden', 'Produkt', 'Farma Doubrava', 'Farma Rychvald', 'Váha'] as $column) {
            self::assertSelectorTextContains('thead', $column);
        }
    }

    protected function insertCalendarRow(int $week, int $year, array $row = []): int
    {
        return $this->insert('calendar_alive', $row + [
            'week_number' => $week,
            'year' => $year,
            'product_tp_id' => 1,
            'farma_doubrava' => 0,
            'farma_rychvald' => 0,
            'weight_from' => 5,
            'weight_to' => 7,
        ]);
    }
}
