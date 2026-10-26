<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

final class EditCalendarAliveTest extends EditCalendarTestCase
{
    use CalendarAliveSection;

    public function testEdits(): void
    {
        $crawler = $this->openPage(self::path() . '/' . self::FIXTURE_ID);

        $form = $this->formByButton($crawler);
        self::assertSame('2', $this->fieldValue($form, 'calendar_alive[product_tp_id]'));
        self::assertSame('20', $this->fieldValue($form, 'calendar_alive[week_number]'));
        self::assertSame('2025', $this->fieldValue($form, 'calendar_alive[year]'));
        self::assertSame(9.0, (float) $this->fieldValue($form, 'calendar_alive[weight_from]'));
        self::assertSame(11.0, (float) $this->fieldValue($form, 'calendar_alive[weight_to]'));
        self::assertNotNull($this->fieldValue($form, 'calendar_alive[farma_doubrava]'), 'Farma Doubrava must be checked.');
        self::assertNull($this->fieldValue($form, 'calendar_alive[farma_rychvald]'), 'Farma Rychvald must be unchecked.');

        $this->submitAdminForm($crawler, self::table(), [
            'product_tp_id' => '1',
            'farma_doubrava' => false,
            'farma_rychvald' => true,
            'week_number' => '21',
            'year' => '2026',
            'weight_from' => '7',
            'weight_to' => '9.5',
        ]);

        $this->assertRedirectsTo(self::path(), self::FIXTURE_ID);

        $row = $this->fetchRow(self::table(), self::FIXTURE_ID);
        self::assertSame(1, self::intValue($row['product_tp_id']));
        self::assertSame(0, self::intValue($row['farma_doubrava']));
        self::assertSame(1, self::intValue($row['farma_rychvald']));
        self::assertSame(21, self::intValue($row['week_number']));
        self::assertSame(2026, self::intValue($row['year']));
        self::assertEqualsWithDelta(7.0, self::floatValue($row['weight_from']), 0.001);
        self::assertEqualsWithDelta(9.5, self::floatValue($row['weight_to']), 0.001);
        self::assertUpdateAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }
}
