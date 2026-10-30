<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\Dashboard\PeriodTotals;
use CeskaKruta\Web\Value\Admin\Dashboard\SalesChannel;
use CeskaKruta\Web\Value\Admin\Dashboard\TopProduct;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Dashboard trends by the day the order was made (`order.ins_dt`, index `ins_dt`), revenue = `order.price_total`.
 */
readonly final class GetDashboardTrends
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Totals per period – one query each (range scan on index `ins_dt`), the number of periods is fixed by the caller.
     *
     * @template TKey of string
     * @param array<TKey, array{\DateTimeImmutable, \DateTimeImmutable}> $periods [from, to) per key
     * @return array<TKey, PeriodTotals>
     */
    public function periodTotals(array $periods): array
    {
        $totals = [];

        foreach ($periods as $key => [$from, $to]) {
            /** @var array{orders_count: int|string, revenue: float|string, registrations: int|string} $row */
            $row = $this->connection
                ->executeQuery('
                    SELECT COUNT(*) AS orders_count, COALESCE(SUM(price_total), 0) AS revenue,
                        (SELECT COUNT(*) FROM user WHERE del_flag = 0 AND ins_dt >= :from AND ins_dt < :to) AS registrations
                    FROM `order`
                    WHERE del_flag = 0 AND ins_dt >= :from AND ins_dt < :to
                ', ['from' => $from->format('Y-m-d H:i:s'), 'to' => $to->format('Y-m-d H:i:s')])
                ->fetchAssociative();

            $totals[$key] = new PeriodTotals((int) $row['orders_count'], (float) $row['revenue'], (int) $row['registrations']);
        }

        return $totals;
    }

    /**
     * Revenue and orders count per month and sales channel since `$from` (first day of a month).
     *
     * @return array<string, array<string, PeriodTotals>> `Y-m` => `SalesChannel` value => totals
     */
    public function monthlyByChannel(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT DATE_FORMAT(o.ins_dt, \'%Y-%m\') AS month, o.place_id,
                    MAX(p.is_cool_balik) AS is_cool_balik, MAX(p.force_delivery_address) AS force_delivery_address,
                    MAX(p.allow_only_wholesale) AS allow_only_wholesale,
                    COUNT(*) AS orders_count, SUM(o.price_total) AS revenue
                FROM `order` o
                LEFT JOIN place p ON p.id = o.place_id
                WHERE o.del_flag = 0 AND o.ins_dt >= :from AND o.ins_dt < :to
                GROUP BY month, o.place_id
            ', ['from' => $from->format('Y-m-d H:i:s'), 'to' => $to->format('Y-m-d H:i:s')])
            ->fetchAllAssociative();

        $months = [];

        foreach ($rows as $row) {
            /** @var array{month: string, is_cool_balik: null|int, force_delivery_address: null|int, allow_only_wholesale: null|int, orders_count: int|string, revenue: float|string} $row */
            $channel = SalesChannel::fromPlaceFlags((bool) $row['allow_only_wholesale'], (bool) $row['is_cool_balik'], (bool) $row['force_delivery_address'])->value;
            $previous = $months[$row['month']][$channel] ?? new PeriodTotals(0, 0.0);
            $months[$row['month']][$channel] = new PeriodTotals(
                $previous->ordersCount + (int) $row['orders_count'],
                $previous->revenue + (float) $row['revenue'],
            );
        }

        return $months;
    }

    /**
     * @return list<TopProduct>
     */
    public function topProducts(\DateTimeImmutable $since, int $limit): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT oi.product_id, MAX(pr.name) AS name, MAX(pr.unit) AS unit,
                    SUM(oi.amount) AS amount, SUM(oi.price_total) AS revenue, COUNT(DISTINCT o.id) AS orders_count
                FROM `order` o
                JOIN order_item oi ON oi.order_id = o.id AND oi.del_flag = 0
                JOIN product pr ON pr.id = oi.product_id
                WHERE o.del_flag = 0 AND o.ins_dt >= :since
                GROUP BY oi.product_id
                ORDER BY revenue DESC
                LIMIT :limit
            ', ['since' => $since->format('Y-m-d H:i:s'), 'limit' => $limit], ['limit' => ParameterType::INTEGER])
            ->fetchAllAssociative();

        return array_map(static function (array $row): TopProduct {
            /** @var array{product_id: int, name: null|string, unit: null|string, amount: float|string, revenue: float|string, orders_count: int|string} $row */

            return new TopProduct(
                productId: $row['product_id'],
                name: (string) $row['name'],
                unit: (string) $row['unit'],
                amount: (float) $row['amount'],
                revenue: (float) $row['revenue'],
                ordersCount: (int) $row['orders_count'],
            );
        }, $rows);
    }
}
