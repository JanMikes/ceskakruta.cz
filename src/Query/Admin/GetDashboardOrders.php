<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\Dashboard\OrderGroup;
use CeskaKruta\Web\Value\Admin\Dashboard\SalesChannel;
use Doctrine\DBAL\Connection;

/**
 * Raw dashboard reads on `order.date` (customer day). Every method is a single aggregated query over a date range
 * (index `order.date`), mapping to the expedition day is done by `DashboardDays` on the already grouped rows.
 */
readonly final class GetDashboardOrders
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<OrderGroup>
     */
    public function groups(\DateTimeImmutable $from, null|\DateTimeImmutable $to): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT o.date, o.place_id, MAX(p.name) AS place_name, MAX(p.is_cool_balik) AS is_cool_balik,
                    MAX(p.force_delivery_address) AS force_delivery_address, MAX(p.allow_only_wholesale) AS allow_only_wholesale,
                    COUNT(*) AS orders_count,
                    SUM(o.price_total) AS revenue,
                    SUM(TRIM(o.note) <> \'\') AS with_note_count,
                    SUM(o.pohoda_sync_dt IS NULL) AS pohoda_missing_count,
                    SUM(p.is_cool_balik = 1 AND o.boxxi_sync_dt IS NULL) AS boxxi_missing_count
                FROM `order` o
                LEFT JOIN place p ON p.id = o.place_id
                WHERE o.del_flag = 0 AND o.date >= :from' . ($to !== null ? ' AND o.date <= :to' : '') . '
                GROUP BY o.date, o.place_id
            ', array_filter([
                'from' => $from->format('Y-m-d'),
                'to' => $to?->format('Y-m-d'),
            ], static fn (null|string $value): bool => $value !== null))
            ->fetchAllAssociative();

        return array_map(static function (array $row): OrderGroup {
            /**
             * @var array{
             *     date: string,
             *     place_id: null|int,
             *     place_name: null|string,
             *     is_cool_balik: null|int,
             *     force_delivery_address: null|int,
             *     allow_only_wholesale: null|int,
             *     orders_count: int|string,
             *     revenue: null|float|string,
             *     with_note_count: null|int|string,
             *     pohoda_missing_count: null|int|string,
             *     boxxi_missing_count: null|int|string,
             * } $row
             */
            $place = self::place($row);

            return new OrderGroup(
                customerDate: new \DateTimeImmutable($row['date']),
                place: $place,
                channel: SalesChannel::fromPlaceFlags((bool) $row['allow_only_wholesale'], $place->isCoolBalik, $place->forceDeliveryAddress),
                ordersCount: (int) $row['orders_count'],
                revenue: (float) $row['revenue'],
                withNoteCount: (int) $row['with_note_count'],
                pohodaMissingCount: (int) $row['pohoda_missing_count'],
                boxxiMissingCount: (int) $row['boxxi_missing_count'],
            );
        }, $rows);
    }

    /**
     * Light order rows (no items) with customer day in the range, newest first.
     *
     * @return list<array{id: int, name: string, date: \DateTimeImmutable, place: AdminOrderPlace, channel: SalesChannel, price_total: float, has_note: bool, pohoda_synced: bool, boxxi_synced: bool}>
     */
    public function orders(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT o.id, o.name, o.date, o.price_total, TRIM(o.note) <> \'\' AS has_note,
                    o.pohoda_sync_dt IS NOT NULL AS pohoda_synced, o.boxxi_sync_dt IS NOT NULL AS boxxi_synced,
                    o.place_id, p.name AS place_name, p.is_cool_balik, p.force_delivery_address, p.allow_only_wholesale
                FROM `order` o
                LEFT JOIN place p ON p.id = o.place_id
                WHERE o.del_flag = 0 AND o.date >= :from AND o.date <= :to
                ORDER BY o.id DESC
            ', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')])
            ->fetchAllAssociative();

        return array_map(static function (array $row): array {
            /**
             * @var array{
             *     id: int,
             *     name: string,
             *     date: string,
             *     price_total: float|string,
             *     has_note: int|string,
             *     pohoda_synced: int|string,
             *     boxxi_synced: int|string,
             *     place_id: null|int,
             *     place_name: null|string,
             *     is_cool_balik: null|int,
             *     force_delivery_address: null|int,
             *     allow_only_wholesale: null|int,
             * } $row
             */
            $place = self::place($row);

            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'date' => new \DateTimeImmutable($row['date']),
                'place' => $place,
                'channel' => SalesChannel::fromPlaceFlags((bool) $row['allow_only_wholesale'], $place->isCoolBalik, $place->forceDeliveryAddress),
                'price_total' => (float) $row['price_total'],
                'has_note' => (bool) $row['has_note'],
                'pohoda_synced' => (bool) $row['pohoda_synced'],
                'boxxi_synced' => (bool) $row['boxxi_synced'],
            ];
        }, $rows);
    }

    /**
     * Items summed per (customer day, place, product) – mapped to the expedition day and summed per product by `DashboardDays`.
     *
     * @return list<array{date: \DateTimeImmutable, place: AdminOrderPlace, product_id: int, product_name: string, unit: string, amount: float, sliced_amount: float, packed_amount: float, orders_count: int}>
     */
    public function items(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT o.date, o.place_id, MAX(p.name) AS place_name, MAX(p.is_cool_balik) AS is_cool_balik,
                    MAX(p.force_delivery_address) AS force_delivery_address,
                    oi.product_id, MAX(pr.name) AS product_name, MAX(pr.unit) AS unit,
                    SUM(oi.amount) AS amount,
                    SUM(IF(oi.is_sliced = 1, oi.amount, 0)) AS sliced_amount,
                    SUM(IF(oi.is_packed = 1, oi.amount, 0)) AS packed_amount,
                    COUNT(DISTINCT o.id) AS orders_count
                FROM `order` o
                JOIN order_item oi ON oi.order_id = o.id AND oi.del_flag = 0
                JOIN product pr ON pr.id = oi.product_id
                LEFT JOIN place p ON p.id = o.place_id
                WHERE o.del_flag = 0 AND o.date >= :from AND o.date <= :to
                GROUP BY o.date, o.place_id, oi.product_id
            ', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')])
            ->fetchAllAssociative();

        return array_map(static function (array $row): array {
            /**
             * @var array{
             *     date: string,
             *     place_id: null|int,
             *     place_name: null|string,
             *     is_cool_balik: null|int,
             *     force_delivery_address: null|int,
             *     product_id: int,
             *     product_name: null|string,
             *     unit: null|string,
             *     amount: float|string,
             *     sliced_amount: float|string,
             *     packed_amount: float|string,
             *     orders_count: int|string,
             * } $row
             */

            return [
                'date' => new \DateTimeImmutable($row['date']),
                'place' => self::place($row),
                'product_id' => $row['product_id'],
                'product_name' => (string) $row['product_name'],
                'unit' => (string) $row['unit'],
                'amount' => (float) $row['amount'],
                'sliced_amount' => (float) $row['sliced_amount'],
                'packed_amount' => (float) $row['packed_amount'],
                'orders_count' => (int) $row['orders_count'],
            ];
        }, $rows);
    }

    /**
     * @param array{place_id: null|int, place_name: null|string, is_cool_balik: null|int, force_delivery_address: null|int} $row
     */
    private static function place(array $row): AdminOrderPlace
    {
        return new AdminOrderPlace(
            id: (int) $row['place_id'],
            name: (string) $row['place_name'],
            isCoolBalik: (bool) $row['is_cool_balik'],
            forceDeliveryAddress: (bool) $row['force_delivery_address'],
        );
    }
}
