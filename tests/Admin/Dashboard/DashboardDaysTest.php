<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Dashboard;

use CeskaKruta\Web\Services\Admin\Dashboard\DashboardDays;
use CeskaKruta\Web\Tests\Support\IntegrationTestCase;
use CeskaKruta\Web\Value\Admin\Dashboard\DateBasis;
use CeskaKruta\Web\Value\Admin\Dashboard\DayOrder;
use CeskaKruta\Web\Value\Admin\Dashboard\DayStats;
use CeskaKruta\Web\Value\Admin\Dashboard\PlaceFilter;
use CeskaKruta\Web\Value\Admin\Dashboard\PrepItem;
use CeskaKruta\Web\Value\Admin\Dashboard\TurkeyPrepGroup;

final class DashboardDaysTest extends IntegrationTestCase
{
    /** @var array{A: int, B: int, C: int, D: int, E: int} */
    private array $orders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orders = DashboardOrdersFixture::insert($this->connection());
    }

    public function testDaysByExpeditionMoveCoolBalikAndShopOrdersEarlier(): void
    {
        $days = $this->days(DateBasis::Expedition);

        self::assertSame([
            '2030-03-11' => 1,
            '2030-03-12' => 0,
            '2030-03-13' => 0,
            '2030-03-14' => 2,
            '2030-03-15' => 1,
        ], array_map(static fn (DayStats $day): int => $day->ordersCount, $days));

        self::assertSame(['delivery' => 1, 'coolbalik' => 1], $days['2030-03-14']->byChannel);
        self::assertSame(1500.0, $days['2030-03-14']->revenue);
        self::assertSame(1, $days['2030-03-14']->withNoteCount);
        self::assertSame(1, $days['2030-03-14']->pohodaMissingCount);
        self::assertSame(1, $days['2030-03-14']->boxxiMissingCount);
    }

    public function testDaysByCustomerDateKeepOrderDate(): void
    {
        self::assertSame([
            '2030-03-11' => 0,
            '2030-03-12' => 0,
            '2030-03-13' => 1,
            '2030-03-14' => 1,
            '2030-03-15' => 2,
        ], array_map(static fn (DayStats $day): int => $day->ordersCount, $this->days(DateBasis::Customer)));
    }

    public function testPrepListSumsAllOrdersOfTheExpeditionDay(): void
    {
        $prep = $this->dashboardDays()->prepList(DateBasis::Expedition, new \DateTimeImmutable('2030-03-14'));

        self::assertSame([
            ['Krůtí prsa', 5.0, 2.0, 3.0, 2],
            ['Krůtí šunka', 1.0, 0.0, 0.0, 1],
        ], array_map(static fn (PrepItem $item): array => [$item->name, $item->amount, $item->slicedAmount, $item->packedAmount, $item->ordersCount], $prep->others));

        self::assertSame([
            'Krůta' => [['Krůta celá s droby', 7.0, 9.0, 1.0]],
            'Krocan' => [['Půlka krocana bez drobů', 16.0, 19.0, 1.0]],
        ], self::turkeys($prep->turkeys));
        self::assertSame([1.0, 0.5], array_map(static fn (TurkeyPrepGroup $group): float => $group->wholePieces(), $prep->turkeys));
    }

    public function testWholeTurkeysAreSplitByWeightClassWithHalvesAfterTheWhole(): void
    {
        $this->connection()->insert('`order`', [
            'place_id' => 1,
            'date' => '2030-03-15',
            'email' => 'dashboard@example.test',
            'phone' => '+420600000000',
            'name' => 'Dashboard heavy',
            'note' => '',
            'price_total' => 900,
            'ins_dt' => '2030-03-10 10:00:00',
        ]);
        $this->connection()->insert('order_item', [
            'order_id' => (int) $this->connection()->lastInsertId(),
            'product_id' => 1,
            'amount' => 1,
            'price_per_unit' => 900,
            'price_packing' => 0,
            'price_total' => 900,
            'weight_from' => 7,
            'weight_to' => 9,
            'ins_dt' => '2030-03-10 10:00:00',
        ]);

        $prep = $this->dashboardDays()->prepList(DateBasis::Customer, new \DateTimeImmutable('2030-03-15'));

        // A (CoolBalík) + C (shop) + the new order, all picked up / delivered on Friday
        self::assertSame([
            'Krůta' => [
                ['Krůta celá s droby', 5.0, 6.0, 2.0],
                ['Krůta celá s droby', 7.0, 9.0, 2.0],
                ['Půlka krůty bez drobů', 5.0, 6.0, 1.0],
            ],
            'Krocan' => [['Půlka krocana bez drobů', 16.0, 19.0, 1.0]],
        ], self::turkeys($prep->turkeys));
        self::assertSame([4.5, 0.5], array_map(static fn (TurkeyPrepGroup $group): float => $group->wholePieces(), $prep->turkeys));
    }

    public function testOwnShopsFilterLeavesOutOtherPlaces(): void
    {
        $own = PlaceFilter::fromValue(PlaceFilter::OWN_SHOPS);

        // Only C (Rychvald shop, Fri 03-15); A, B (CoolBalík, delivery) and D (other shop 30) are left out
        self::assertSame(
            ['2030-03-11' => 0, '2030-03-12' => 0, '2030-03-13' => 0, '2030-03-14' => 0, '2030-03-15' => 1],
            array_map(static fn (DayStats $day): int => $day->ordersCount, $this->dashboardDays()->days(DateBasis::Expedition, new \DateTimeImmutable('2030-03-11'), new \DateTimeImmutable('2030-03-15'), $own)),
        );
        self::assertTrue($this->dashboardDays()->prepList(DateBasis::Expedition, new \DateTimeImmutable('2030-03-14'), $own)->isEmpty());
        self::assertSame([], $this->dashboardDays()->orders(DateBasis::Expedition, new \DateTimeImmutable('2030-03-14'), $own));

        $prep = $this->dashboardDays()->prepList(DateBasis::Expedition, new \DateTimeImmutable('2030-03-15'), $own);
        self::assertSame([2.5], array_map(static fn (TurkeyPrepGroup $group): float => $group->wholePieces(), $prep->turkeys));
        self::assertSame(
            [$this->orders['C']],
            array_map(static fn (DayOrder $order): int => $order->id, $this->dashboardDays()->orders(DateBasis::Expedition, new \DateTimeImmutable('2030-03-15'), $own)),
        );
    }

    public function testSinglePlaceFilter(): void
    {
        $orders = $this->dashboardDays()->orders(DateBasis::Expedition, new \DateTimeImmutable('2030-03-14'), PlaceFilter::fromValue('21'));

        self::assertSame([$this->orders['A']], array_map(static fn (DayOrder $order): int => $order->id, $orders));
    }

    public function testInvalidPlaceFilterMeansAllPlaces(): void
    {
        foreach (['', 'abc', '0', '-1', '1 OR 1=1'] as $value) {
            self::assertTrue(PlaceFilter::fromValue($value)->isAll(), $value);
        }
    }

    public function testOrdersOfTheDayCarryBothDates(): void
    {
        $orders = $this->dashboardDays()->orders(DateBasis::Expedition, new \DateTimeImmutable('2030-03-14'));
        $byId = array_combine(array_map(static fn (DayOrder $order): int => $order->id, $orders), $orders);

        self::assertEqualsCanonicalizing([$this->orders['A'], $this->orders['B']], array_keys($byId));
        self::assertSame('2030-03-15', $byId[$this->orders['A']]->customerDate->format('Y-m-d'));
        self::assertSame('2030-03-14', $byId[$this->orders['A']]->expeditionDate->format('Y-m-d'));
        self::assertFalse($byId[$this->orders['A']]->boxxiSynced);
        self::assertNull($byId[$this->orders['B']]->boxxiSynced, 'Boxxi does not apply to own delivery');
    }

    public function testSyncAttentionSplitsByExpeditionDay(): void
    {
        $attention = $this->dashboardDays()->syncAttention(new \DateTimeImmutable('2030-03-14'));

        // Overdue = expedition 03-14 or earlier: A (03-14), D (03-11); upcoming: C (03-15), E (03-18)
        self::assertSame(2, $attention->pohodaOverdue);
        self::assertSame(2, $attention->pohodaUpcoming);
        self::assertSame(1, $attention->boxxiOverdue);
        self::assertSame(0, $attention->boxxiUpcoming);
    }

    public function testSyncAttentionIgnoresOrdersOlderThanTheLookback(): void
    {
        $attention = $this->dashboardDays()->syncAttention(new \DateTimeImmutable('2030-04-30'));

        self::assertTrue($attention->isEmpty());
    }

    /**
     * @return array<string, DayStats>
     */
    private function days(DateBasis $basis): array
    {
        return $this->dashboardDays()->days($basis, new \DateTimeImmutable('2030-03-11'), new \DateTimeImmutable('2030-03-15'));
    }

    /**
     * @param list<TurkeyPrepGroup> $groups
     * @return array<string, list<array{string, null|float, null|float, float}>>
     */
    private static function turkeys(array $groups): array
    {
        $result = [];

        foreach ($groups as $group) {
            $result[$group->label] = array_map(static fn (PrepItem $item): array => [$item->name, $item->weightFrom, $item->weightTo, $item->amount], $group->items);
        }

        return $result;
    }

    private function dashboardDays(): DashboardDays
    {
        return self::getContainer()->get(DashboardDays::class);
    }
}
