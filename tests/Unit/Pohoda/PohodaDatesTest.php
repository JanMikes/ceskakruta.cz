<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Pohoda;

use CeskaKruta\Web\Services\Pohoda\PohodaDates;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Expected values = output of the OLD `Pohoda::calculateDateTo` / `calculateDateFrom` (called verbatim via reflection,
 * tests/fixtures/pohoda/_renderer/dates.phps) for the week Mon 2024-06-17 … Sun 2024-06-23,
 * identical to the table derived by hand from the old code.
 */
final class PohodaDatesTest extends TestCase
{
    private const WEEK = [
        'Mon' => '2024-06-17',
        'Tue' => '2024-06-18',
        'Wed' => '2024-06-19',
        'Thu' => '2024-06-20',
        'Fri' => '2024-06-21',
        'Sat' => '2024-06-22',
        'Sun' => '2024-06-23',
    ];

    /** dateTo per place kind, keyed by order date weekday */
    private const DATE_TO = [
        // shop other than place 1, without delivery: Tue/Wed → previous Monday, Fri → previous Thursday
        'shop' => ['Mon' => '2024-06-17', 'Tue' => '2024-06-17', 'Wed' => '2024-06-17', 'Thu' => '2024-06-20', 'Fri' => '2024-06-20', 'Sat' => '2024-06-22', 'Sun' => '2024-06-23'],
        // place 1 (Rychvald) keeps the order date
        'shop place 1' => ['Mon' => '2024-06-17', 'Tue' => '2024-06-18', 'Wed' => '2024-06-19', 'Thu' => '2024-06-20', 'Fri' => '2024-06-21', 'Sat' => '2024-06-22', 'Sun' => '2024-06-23'],
        // own delivery keeps the order date
        'delivery' => ['Mon' => '2024-06-17', 'Tue' => '2024-06-18', 'Wed' => '2024-06-19', 'Thu' => '2024-06-20', 'Fri' => '2024-06-21', 'Sat' => '2024-06-22', 'Sun' => '2024-06-23'],
        // CoolBalík: Mon → prev Fri, Tue → prev Mon, Wed/Thu → prev Tue, Fri/Sat → prev Thu, Sun → last Tue
        'CoolBalík' => ['Mon' => '2024-06-14', 'Tue' => '2024-06-17', 'Wed' => '2024-06-18', 'Thu' => '2024-06-18', 'Fri' => '2024-06-20', 'Sat' => '2024-06-20', 'Sun' => '2024-06-18'],
    ];

    /** dateFrom keyed by expedition (dateTo) weekday */
    private const DATE_FROM_GENERIC = ['Mon' => '2024-06-14', 'Tue' => '2024-06-14', 'Wed' => '2024-06-17', 'Thu' => '2024-06-18', 'Fri' => '2024-06-18', 'Sat' => '2024-06-21', 'Sun' => '2024-06-21'];
    private const DATE_FROM_COOL_BALIK = ['Mon' => '2024-06-14', 'Tue' => '2024-06-14', 'Wed' => '2024-06-14', 'Thu' => '2024-06-17', 'Fri' => '2024-06-17', 'Sat' => '2024-06-21', 'Sun' => '2024-06-21'];
    private const DATE_FROM_COOL_BALIK_TURKEYS = ['Mon' => '2024-06-14', 'Tue' => '2024-06-17', 'Wed' => '2024-06-14', 'Thu' => '2024-06-18', 'Fri' => '2024-06-17', 'Sat' => '2024-06-21', 'Sun' => '2024-06-21'];

    #[DataProvider('provideDateTo')]
    public function testDateTo(AdminOrderPlace $place, string $orderDate, string $expected): void
    {
        $dateTo = (new PohodaDates())->dateTo(new \DateTimeImmutable($orderDate), $place);

        self::assertSame($expected, $dateTo->format('Y-m-d'));
    }

    /**
     * @return \Generator<string, array{AdminOrderPlace, string, string}>
     */
    public static function provideDateTo(): \Generator
    {
        foreach (self::places() as $kind => $place) {
            foreach (self::WEEK as $day => $date) {
                yield sprintf('%s, order on %s %s', $kind, $day, $date) => [$place, $date, self::DATE_TO[$kind][$day]];
            }
        }
    }

    #[DataProvider('provideDateFrom')]
    public function testDateFrom(AdminOrderPlace $place, string $dateTo, bool $wholeTurkeysOrder, string $expected): void
    {
        $dateFrom = (new PohodaDates())->dateFrom(new \DateTimeImmutable($dateTo), $place, $wholeTurkeysOrder);

        self::assertSame($expected, $dateFrom->format('Y-m-d'));
    }

    /**
     * Every expedition weekday × {shop, shop place 1, delivery, CoolBalík} × {whole turkeys order, other}.
     *
     * @return \Generator<string, array{AdminOrderPlace, string, bool, string}>
     */
    public static function provideDateFrom(): \Generator
    {
        foreach (self::places() as $kind => $place) {
            foreach (self::WEEK as $day => $date) {
                foreach ([false, true] as $turkeys) {
                    $expected = match (true) {
                        $kind !== 'CoolBalík' => self::DATE_FROM_GENERIC[$day],
                        $turkeys => self::DATE_FROM_COOL_BALIK_TURKEYS[$day],
                        default => self::DATE_FROM_COOL_BALIK[$day],
                    };

                    yield sprintf('%s, expedition %s %s, %s', $kind, $day, $date, $turkeys ? 'turkeys' : 'other') => [$place, $date, $turkeys, $expected];
                }
            }
        }
    }

    /**
     * Chained like old `sendOrderRequest`: order date → dateTo → dateFrom (+ split decision) for every weekday.
     */
    #[DataProvider('provideOrderDates')]
    public function testOrderDateToSlaughterDate(AdminOrderPlace $place, string $orderDate, bool $expectedSplit, string $expectedDateFrom, null|string $expectedTurkeysDateFrom): void
    {
        $dates = new PohodaDates();
        $dateTo = $dates->dateTo(new \DateTimeImmutable($orderDate), $place);

        self::assertSame($expectedSplit, $dates->splitsWholeTurkeys($dateTo, $place));
        self::assertSame($expectedDateFrom, $dates->dateFrom($dateTo, $place, false)->format('Y-m-d'));

        if ($expectedTurkeysDateFrom !== null) {
            self::assertSame($expectedTurkeysDateFrom, $dates->dateFrom($dateTo, $place, true)->format('Y-m-d'));
        }
    }

    /**
     * @return \Generator<string, array{AdminOrderPlace, string, bool, string, null|string}>
     */
    public static function provideOrderDates(): \Generator
    {
        $coolBalik = GoldenOrders::coolBalikPlace();

        // CoolBalík: expedition Fri / Mon / Tue / Tue / Thu / Thu / Tue
        yield 'CoolBalík Mon (exp. Fri 14th)' => [$coolBalik, '2024-06-17', false, '2024-06-10', null];
        yield 'CoolBalík Tue (exp. Mon 17th)' => [$coolBalik, '2024-06-18', false, '2024-06-14', null];
        yield 'CoolBalík Wed (exp. Tue 18th, split)' => [$coolBalik, '2024-06-19', true, '2024-06-14', '2024-06-17'];
        yield 'CoolBalík Thu (exp. Tue 18th, split)' => [$coolBalik, '2024-06-20', true, '2024-06-14', '2024-06-17'];
        yield 'CoolBalík Fri (exp. Thu 20th, split)' => [$coolBalik, '2024-06-21', true, '2024-06-17', '2024-06-18'];
        yield 'CoolBalík Sat (exp. Thu 20th, split)' => [$coolBalik, '2024-06-22', true, '2024-06-17', '2024-06-18'];
        yield 'CoolBalík Sun (exp. Tue 18th, split)' => [$coolBalik, '2024-06-23', true, '2024-06-14', '2024-06-17'];

        // Never split outside CoolBalík, even on Tue/Thu expedition
        yield 'delivery Tue' => [GoldenOrders::deliveryPlace(), '2024-06-18', false, '2024-06-14', null];
        yield 'delivery Thu' => [GoldenOrders::deliveryPlace(), '2024-06-20', false, '2024-06-18', null];
        yield 'shop place 1 Tue' => [GoldenOrders::shopPlace(), '2024-06-18', false, '2024-06-14', null];
        yield 'shop Wed (exp. Mon)' => [GoldenOrders::otherShopPlace(), '2024-06-19', false, '2024-06-14', null];
        yield 'shop Fri (exp. Thu)' => [GoldenOrders::otherShopPlace(), '2024-06-21', false, '2024-06-18', null];
    }

    public function testDoesNotModifyInput(): void
    {
        $orderDate = new \DateTimeImmutable('2024-06-21');
        $dates = new PohodaDates();

        $dateTo = $dates->dateTo($orderDate, GoldenOrders::coolBalikPlace());
        $dates->dateFrom($dateTo, GoldenOrders::coolBalikPlace(), true);

        self::assertSame('2024-06-21', $orderDate->format('Y-m-d'));
        self::assertSame('2024-06-20', $dateTo->format('Y-m-d'));
    }

    /**
     * @return array<string, AdminOrderPlace>
     */
    private static function places(): array
    {
        return [
            'shop' => GoldenOrders::otherShopPlace(),
            'shop place 1' => GoldenOrders::shopPlace(),
            'delivery' => GoldenOrders::deliveryPlace(),
            'CoolBalík' => GoldenOrders::coolBalikPlace(),
        ];
    }
}
