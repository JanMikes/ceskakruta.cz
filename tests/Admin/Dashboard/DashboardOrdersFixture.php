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
 *
 * Items: A 2 kg prsa (sliced) + whole krůta 7–9 kg + half krocan 16–19 kg; B 3 kg prsa (packed) + 1 šunka;
 * C 1 kg prsa + 2 whole krůta 5–6 kg + 1 half krůta 5–6 kg.
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

        $item = static function (int $orderId, int $productId, float $amount, bool $sliced, bool $packed, null|float $weightFrom = null, null|float $weightTo = null) use ($connection): void {
            $connection->insert('order_item', [
                'order_id' => $orderId,
                'product_id' => $productId,
                'is_sliced' => (int) $sliced,
                'is_packed' => (int) $packed,
                'amount' => $amount,
                'price_per_unit' => 100,
                'price_packing' => 0,
                'price_total' => (int) ($amount * 100),
                'weight_from' => $weightFrom,
                'weight_to' => $weightTo,
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
        $item($ids['A'], 1, 1, false, false, 7, 9);
        $item($ids['A'], 48, 1, false, false, 16, 19);
        $item($ids['C'], 1, 2, false, false, 5, 6);
        $item($ids['C'], 2, 1, false, false, 5, 6);

        return $ids;
    }
}
