<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Dashboard;

use CeskaKruta\Web\Services\Admin\Dashboard\DashboardDays;
use CeskaKruta\Web\Tests\Support\IntegrationTestCase;
use CeskaKruta\Web\Value\Admin\Dashboard\DateBasis;
use CeskaKruta\Web\Value\Admin\Dashboard\DayOrder;
use CeskaKruta\Web\Value\Admin\Dashboard\DayStats;
use CeskaKruta\Web\Value\Admin\Dashboard\PrepItem;

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

    public function testPrepItemsSumAllOrdersOfTheExpeditionDay(): void
    {
        $items = $this->dashboardDays()->prepItems(DateBasis::Expedition, new \DateTimeImmutable('2030-03-14'));

        self::assertSame([
            ['Krůtí prsa', 5.0, 2.0, 3.0, 2],
            ['Krůtí šunka', 1.0, 0.0, 0.0, 1],
        ], array_map(static fn (PrepItem $item): array => [$item->name, $item->amount, $item->slicedAmount, $item->packedAmount, $item->ordersCount], $items));
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

    private function dashboardDays(): DashboardDays
    {
        return self::getContainer()->get(DashboardDays::class);
    }
}
