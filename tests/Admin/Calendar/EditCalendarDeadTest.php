<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

final class EditCalendarDeadTest extends EditCalendarTestCase
{
    use CalendarDeadSection;

    public function testEdits(): void
    {
        $crawler = $this->openPage(self::path() . '/' . self::FIXTURE_ID);

        // Prefilled with the current row
        $form = $this->formByButton($crawler);
        self::assertSame('20', $this->fieldValue($form, 'calendar_dead[week_number]'));
        self::assertSame('2025', $this->fieldValue($form, 'calendar_dead[year]'));
        self::assertSame('5', $this->fieldValue($form, 'calendar_dead[week_age]'));
        self::assertSame('2-3 kg', $this->fieldValue($form, 'calendar_dead[weight]'));
        self::assertSame('Fixture poznámka', $this->fieldValue($form, 'calendar_dead[note]'));
        self::assertNotNull($this->fieldValue($form, 'calendar_dead[farma_doubrava]'), 'Farma Doubrava must be checked.');
        self::assertNull($this->fieldValue($form, 'calendar_dead[farma_rychvald]'), 'Farma Rychvald must be unchecked.');

        $this->submitAdminForm($crawler, self::table(), [
            'farma_doubrava' => false,
            'farma_rychvald' => true,
            'week_number' => '21',
            'year' => '2026',
            'week_age' => '',
            'weight' => '4-5 kg',
            'price_mix' => '125',
            'price_man' => '135',
            'price_woman' => '115',
            'note' => 'Upraveno',
        ]);

        $this->assertRedirectsTo(self::path(), self::FIXTURE_ID);

        $row = $this->fetchRow(self::table(), self::FIXTURE_ID);
        self::assertSame(0, self::intValue($row['farma_doubrava']));
        self::assertSame(1, self::intValue($row['farma_rychvald']));
        self::assertSame(21, self::intValue($row['week_number']));
        self::assertSame(2026, self::intValue($row['year']));
        self::assertNull($row['week_age']);
        self::assertSame('4-5 kg', $row['weight']);
        self::assertSame('125', $row['price_mix']);
        self::assertSame('135', $row['price_man']);
        self::assertSame('115', $row['price_woman']);
        self::assertSame('Upraveno', $row['note']);
        self::assertUpdateAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }
}
