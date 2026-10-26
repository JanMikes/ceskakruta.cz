<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Admin;

use CeskaKruta\Web\Value\Admin\PlaceDayHint;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlaceDayHintTest extends TestCase
{
    /**
     * Expected values follow the old JS (`resultDay = day - daysBefore`, wrapped by ±7 until 1–7),
     * which equals the old Latte list formula `(day - daysBefore) % 7`, `<= 0 → +7`.
     *
     * @return iterable<string, array{int, int, int}>
     */
    public static function provideOrderDay(): iterable
    {
        yield 'same day' => [1, 0, 1];
        yield 'sunday same day' => [7, 0, 7];
        yield 'monday 1 before -> sunday' => [1, 1, 7];
        yield 'monday 2 before -> saturday' => [1, 2, 6];
        yield 'wednesday 2 before -> monday' => [3, 2, 1];
        yield 'wednesday 3 before -> sunday' => [3, 3, 7];
        yield 'sunday 6 before -> monday' => [7, 6, 1];
        yield 'friday 2 before -> wednesday' => [5, 2, 3];
        yield 'tuesday 3 before -> saturday' => [2, 3, 6];
        yield 'full week before -> same day' => [2, 7, 2];
        yield 'sunday 7 before -> sunday' => [7, 7, 7];
        yield 'friday 9 before -> wednesday' => [5, 9, 3];
        yield 'sunday 9 before -> friday' => [7, 9, 5];
        yield 'two weeks before -> same day' => [1, 14, 1];
        yield 'thursday 15 before -> wednesday' => [4, 15, 3];
        yield 'saturday 20 before -> sunday' => [6, 20, 7];
    }

    #[DataProvider('provideOrderDay')]
    public function testOrderDay(int $deliveryDay, int $daysBefore, int $expected): void
    {
        self::assertSame($expected, PlaceDayHint::orderDay($deliveryDay, $daysBefore));
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function provideInvalidOrderDayInput(): iterable
    {
        yield 'day 0' => [0, 1];
        yield 'day 8' => [8, 1];
        yield 'negative days before' => [3, -1];
    }

    #[DataProvider('provideInvalidOrderDayInput')]
    public function testOrderDayRejectsInvalidInput(int $deliveryDay, int $daysBefore): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PlaceDayHint::orderDay($deliveryDay, $daysBefore);
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function provideDayName(): iterable
    {
        yield 'monday' => [1, 'Pondělí'];
        yield 'tuesday' => [2, 'Úterý'];
        yield 'wednesday' => [3, 'Středa'];
        yield 'thursday' => [4, 'Čtvrtek'];
        yield 'friday' => [5, 'Pátek'];
        yield 'saturday' => [6, 'Sobota'];
        yield 'sunday' => [7, 'Neděle'];
    }

    #[DataProvider('provideDayName')]
    public function testDayName(int $day, string $expected): void
    {
        self::assertSame($expected, PlaceDayHint::dayName($day));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideInvalidDay(): iterable
    {
        yield 'zero' => [0];
        yield 'eight' => [8];
    }

    #[DataProvider('provideInvalidDay')]
    public function testDayNameRejectsInvalidDay(int $day): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PlaceDayHint::dayName($day);
    }
}
