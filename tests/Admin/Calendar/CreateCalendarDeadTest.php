<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

use PHPUnit\Framework\Attributes\DataProvider;

final class CreateCalendarDeadTest extends CreateCalendarTestCase
{
    use CalendarDeadSection;

    public function testCreates(): void
    {
        $crawler = $this->openPage(self::path() . '/pridat');
        $this->submitAdminForm($crawler, self::table(), self::validValues());

        $id = $this->lastInsertedId(self::table());
        self::assertGreaterThan(self::FIXTURE_ID, $id);
        $this->assertRedirectsTo(self::path(), $id);

        $row = $this->fetchRow(self::table(), $id);
        self::assertSame(1, self::intValue($row['farma_doubrava']));
        self::assertSame(0, self::intValue($row['farma_rychvald']));
        self::assertSame(12, self::intValue($row['week_number']));
        self::assertSame((int) date('Y'), self::intValue($row['year']));
        self::assertSame(7, self::intValue($row['week_age']));
        self::assertSame('3-4 kg', $row['weight']);
        self::assertSame('120 Kč', $row['price_mix']);
        self::assertSame('130 Kč', $row['price_man']);
        self::assertSame('110 Kč', $row['price_woman']);
        self::assertSame('Nová poznámka', $row['note']);
        self::assertInsertAudit($row);

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testOptionalFieldsMayBeEmpty(): void
    {
        $crawler = $this->openPage(self::path() . '/pridat');
        $this->submitAdminForm($crawler, self::table(), [
            'farma_doubrava' => false,
            'week_age' => '',
            'weight' => '',
            'price_mix' => '',
            'price_man' => '',
            'price_woman' => '',
            'note' => '',
        ] + self::validValues());

        $id = $this->lastInsertedId(self::table());
        $this->assertRedirectsTo(self::path(), $id);

        $row = $this->fetchRow(self::table(), $id);
        self::assertSame(0, self::intValue($row['farma_doubrava']));
        self::assertSame(0, self::intValue($row['farma_rychvald']));
        self::assertNull($row['week_age']);
    }

    #[DataProvider('provideWeekAges')]
    public function testWeekAgeOptionalInt(string $weekAge, bool $valid): void
    {
        $this->assertCreateOutcome(['week_age' => $weekAge], $valid);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideWeekAges(): iterable
    {
        yield 'empty' => ['', true];
        yield 'integer' => ['8', true];
        yield 'decimal' => ['1.5', false];
        yield 'text' => ['abc', false];
    }

    /**
     * Old form: weight / prices max 20 chars, note max 40.
     */
    #[DataProvider('provideMaxLengths')]
    public function testMaxLengths(string $field, int $maxLength): void
    {
        $this->assertCreateOutcome([$field => str_repeat('x', $maxLength)], true);
        $this->assertCreateOutcome([$field => str_repeat('x', $maxLength + 1)], false);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideMaxLengths(): iterable
    {
        yield 'weight' => ['weight', 20];
        yield 'price_mix' => ['price_mix', 20];
        yield 'price_man' => ['price_man', 20];
        yield 'price_woman' => ['price_woman', 20];
        yield 'note' => ['note', 40];
    }
}
