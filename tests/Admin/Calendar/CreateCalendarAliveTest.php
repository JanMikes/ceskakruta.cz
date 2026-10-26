<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

final class CreateCalendarAliveTest extends CreateCalendarTestCase
{
    use CalendarAliveSection;
    use ProductTypeCalendarCreateTests;

    public function testCreates(): void
    {
        $crawler = $this->openPage(self::path() . '/pridat');
        $this->submitAdminForm($crawler, self::table(), self::validValues());

        $id = $this->lastInsertedId(self::table());
        self::assertGreaterThan(self::FIXTURE_ID, $id);
        $this->assertRedirectsTo(self::path(), $id);

        $row = $this->fetchRow(self::table(), $id);
        self::assertSame(2, self::intValue($row['product_tp_id']));
        self::assertSame(12, self::intValue($row['week_number']));
        self::assertSame((int) date('Y'), self::intValue($row['year']));
        self::assertEqualsWithDelta(6.0, self::floatValue($row['weight_from']), 0.001);
        self::assertEqualsWithDelta(8.5, self::floatValue($row['weight_to']), 0.001);
        self::assertSame(0, self::intValue($row['farma_doubrava']));
        self::assertSame(1, self::intValue($row['farma_rychvald']));
        self::assertInsertAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }
}
