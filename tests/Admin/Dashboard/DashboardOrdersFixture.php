<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Dashboard;

use Doctrine\DBAL\Connection;

/**
 * Own orders in March 2030 (no fixture data there): 2030-03-15 is a Friday.
 *
 *   id    place                     customer day   expedition   Pohoda   Boxxi
 *   A     21 CoolBalík              Fri 03-15      Thu 03-14    –        –
 *   B     7  own delivery           Thu 03-14      Thu 03-14    synced   n/a
 *   C     1  Rychvald (shop)        Fri 03-15      Fri 03-15    –        n/a
 *   D     30 other shop             Wed 03-13      Mon 03-11    –        n/a
 *   E     21 CoolBalík              Tue 03-19      Mon 03-18    –        synced
 */
final class DashboardOrdersFixture
{
    /**
     * @return array{A: int, B: int, C: int, D: int, E: int}
     */
    public static function insert(Connection $connection): array
    {
        $order = static function (int $placeId, string $date, float $price, bool $pohodaSynced, bool $boxxiSynced, string $note = '') use ($connection): int {
            $connection->insert('`order`', [
                'place_id' => $placeId,
                'date' => $date,
                'email' => 'dashboard@example.test',
                'phone' => '+420600000000',
                'name' => 'Dashboard ' . $date,
                'note' => $note,
                'price_total' => $price,
                'ins_dt' => '2030-03-10 10:00:00',
                'pohoda_sync_dt' => $pohodaSynced ? '2030-03-10 11:00:00' : null,
                'boxxi_sync_dt' => $boxxiSynced ? '2030-03-10 11:00:00' : null,
            ]);

            return (int) $connection->lastInsertId();
        };

        $item = static function (int $orderId, int $productId, float $amount, bool $sliced, bool $packed) use ($connection): void {
            $connection->insert('order_item', [
                'order_id' => $orderId,
                'product_id' => $productId,
                'is_sliced' => (int) $sliced,
                'is_packed' => (int) $packed,
                'amount' => $amount,
                'price_per_unit' => 100,
                'price_packing' => 0,
                'price_total' => (int) ($amount * 100),
                'ins_dt' => '2030-03-10 10:00:00',
            ]);
        };

        $ids = [
            'A' => $order(21, '2030-03-15', 1000, false, false),
            'B' => $order(7, '2030-03-14', 500, true, false, 'Zvonit'),
            'C' => $order(1, '2030-03-15', 300, false, false),
            'D' => $order(30, '2030-03-13', 200, false, false),
            'E' => $order(21, '2030-03-19', 700, false, true),
        ];

        $item($ids['A'], 100, 2, true, false);
        $item($ids['B'], 100, 3, false, true);
        $item($ids['B'], 101, 1, false, false);
        $item($ids['C'], 100, 1, false, false);

        return $ids;
    }
}
