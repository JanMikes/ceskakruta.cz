<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Dashboard;

use CeskaKruta\Web\Services\Admin\Dashboard\RecurringOrdersWatch;
use CeskaKruta\Web\Tests\Support\IntegrationTestCase;
use CeskaKruta\Web\Value\Admin\Dashboard\ExpectedOrder;
use CeskaKruta\Web\Value\Admin\Dashboard\ExpectedOrderStatus;

/**
 * Fixture users (tests/database/fixtures/40-places-users.sql): 4004 wholesale, place 22 (Tue/Fri 1 day before 23:59),
 * no delivery address; 4005 place 7 (Tue 2 days before 23:59, no Friday), with delivery address.
 * Week Mon 2030-03-11 – Sun 2030-03-17: deliveries Tue 03-12 and Fri 03-15.
 */
final class RecurringOrdersWatchTest extends IntegrationTestCase
{
    private const WHOLESALE_USER = 4004;
    private const DELIVERY_USER = 4005;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringOrder(self::WHOLESALE_USER, 2);
        $this->recurringOrder(self::DELIVERY_USER, 2);
        $this->recurringOrder(self::DELIVERY_USER, 5);
    }

    public function testStatusesAfterTheDeadline(): void
    {
        $expected = $this->expected('2030-03-11 10:00:00');

        self::assertCount(3, $expected);

        // Deadline Sun 03-10 23:59 passed > 1 h ago, still within the 24 h cron window
        $delivery = $expected[self::DELIVERY_USER . '_2030-03-12'];
        self::assertSame(ExpectedOrderStatus::Missing, $delivery->status);
        self::assertSame('2030-03-10 23:59:00', $delivery->deadline?->format('Y-m-d H:i:s'));
        self::assertStringContainsString('zkontrolujte cron', (string) $delivery->problem);

        // Deadline Mon 03-11 23:59 not reached, but the cron would skip the user without delivery address
        $wholesale = $expected[self::WHOLESALE_USER . '_2030-03-12'];
        self::assertSame(ExpectedOrderStatus::Waiting, $wholesale->status);
        self::assertTrue($wholesale->isWholesale);
        self::assertSame('PU Firma s.r.o.', $wholesale->customerName);
        self::assertStringContainsString('doručovací adresu', (string) $wholesale->problem);

        // Place 7 does not take orders for Friday
        $friday = $expected[self::DELIVERY_USER . '_2030-03-15'];
        self::assertSame(ExpectedOrderStatus::Missing, $friday->status);
        self::assertNull($friday->deadline);
        self::assertStringContainsString('neobjednává', (string) $friday->problem);
    }

    public function testJustAfterTheDeadlineTheCronStillHasTime(): void
    {
        self::assertSame(ExpectedOrderStatus::Processing, $this->expected('2030-03-11 00:30:00')[self::DELIVERY_USER . '_2030-03-12']->status);
    }

    public function testMoreThanDayAfterTheDeadlineTheCronWillNotCreateIt(): void
    {
        $expected = $this->expected('2030-03-12 06:00:00');

        self::assertStringContainsString('ručně', (string) $expected[self::DELIVERY_USER . '_2030-03-12']->problem);
        self::assertSame(ExpectedOrderStatus::Missing, $expected[self::WHOLESALE_USER . '_2030-03-12']->status);
        self::assertStringContainsString('doručovací adresu', (string) $expected[self::WHOLESALE_USER . '_2030-03-12']->problem);
    }

    public function testExistingOrderOfTheDayCounts(): void
    {
        $this->connection()->insert('`order`', [
            'user_id' => self::DELIVERY_USER,
            'place_id' => 7,
            'date' => '2030-03-12',
            'email' => 'pu-edit@places-users.test',
            'phone' => '+420600000005',
            'name' => 'PU Editovaný',
            'note' => '',
            'price_total' => 100,
            'ins_dt' => '2030-03-11 00:05:00',
        ]);
        $orderId = (int) $this->connection()->lastInsertId();

        $delivery = $this->expected('2030-03-11 10:00:00')[self::DELIVERY_USER . '_2030-03-12'];

        self::assertSame(ExpectedOrderStatus::Created, $delivery->status);
        self::assertSame($orderId, $delivery->orderId);
    }

    public function testSkippedDelivery(): void
    {
        $this->connection()->insert('recurring_order_skip', [
            'user_id' => self::DELIVERY_USER,
            'day_of_week' => 2,
            'skip_until' => '2030-03-12 00:00:00',
        ]);

        self::assertSame(ExpectedOrderStatus::Skipped, $this->expected('2030-03-11 10:00:00')[self::DELIVERY_USER . '_2030-03-12']->status);
    }

    /**
     * @return array<string, ExpectedOrder> keyed `{userId}_{Y-m-d}`
     */
    private function expected(string $now): array
    {
        $watch = self::getContainer()->get(RecurringOrdersWatch::class);
        $expected = [];

        foreach ($watch->expectedOrders(new \DateTimeImmutable($now), new \DateTimeImmutable('2030-03-11'), new \DateTimeImmutable('2030-03-17')) as $order) {
            $expected[$order->userId . '_' . $order->date->format('Y-m-d')] = $order;
        }

        return $expected;
    }

    private function recurringOrder(int $userId, int $dayOfWeek): void
    {
        $id = $this->fetchString('SELECT UUID()');

        $this->connection()->insert('recurring_order', ['id' => $id, 'user_id' => $userId, 'day_of_week' => $dayOfWeek]);
        $this->connection()->insert('recurring_order_item', [
            'id' => $this->fetchString('SELECT UUID()'),
            'order_id' => $id,
            'product_id' => 100,
            'packages' => '[]',
            'other_package_size_amount' => 2,
            'note' => '',
            'is_sliced' => 0,
            'is_packed' => 0,
        ]);
    }
}
