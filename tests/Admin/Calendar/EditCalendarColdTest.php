<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

final class EditCalendarColdTest extends EditCalendarTestCase
{
    use CalendarColdSection;

    public function testEdits(): void
    {
        $crawler = $this->openPage(self::path() . '/' . self::FIXTURE_ID);

        $form = $this->formByButton($crawler);
        self::assertSame('1', $this->fieldValue($form, 'calendar_cold[product_tp_id]'));
        self::assertSame('20', $this->fieldValue($form, 'calendar_cold[week_number]'));
        self::assertSame('2025', $this->fieldValue($form, 'calendar_cold[year]'));
        self::assertSame(5.0, (float) $this->fieldValue($form, 'calendar_cold[weight_from]'));
        self::assertSame(7.0, (float) $this->fieldValue($form, 'calendar_cold[weight_to]'));

        $this->submitAdminForm($crawler, self::table(), [
            'product_tp_id' => '2',
            'week_number' => '21',
            'year' => '2026',
            'weight_from' => '9',
            'weight_to' => '11.5',
        ]);

        $this->assertRedirectsTo(self::path(), self::FIXTURE_ID);

        $row = $this->fetchRow(self::table(), self::FIXTURE_ID);
        self::assertSame(2, self::intValue($row['product_tp_id']));
        self::assertSame(21, self::intValue($row['week_number']));
        self::assertSame(2026, self::intValue($row['year']));
        self::assertEqualsWithDelta(9.0, self::floatValue($row['weight_from']), 0.001);
        self::assertEqualsWithDelta(11.5, self::floatValue($row['weight_to']), 0.001);
        self::assertUpdateAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }
}
