<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Bulk reads for `RecurringOrdersWatch` – one query each, no per-user lookups.
 */
readonly final class GetRecurringOrdersOverview
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Recurring orders of active users with at least one item.
     *
     * @return list<array{user_id: int, day_of_week: int, name: string, preferred_place_id: null|int, is_wholesale: bool, has_delivery_address: bool}>
     */
    public function recurringOrders(): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT ro.user_id, ro.day_of_week,
                    COALESCE(NULLIF(u.company_name, \'\'), NULLIF(u.name, \'\'), u.email) AS name,
                    u.preferred_place_id, u.allow_wholesale,
                    (u.name IS NOT NULL AND u.phone IS NOT NULL AND u.delivery_street IS NOT NULL
                        AND u.delivery_city IS NOT NULL AND u.delivery_zip IS NOT NULL) AS has_delivery_address
                FROM recurring_order ro
                JOIN user u ON u.id = ro.user_id AND u.active_flag = 1 AND u.del_flag = 0
                WHERE EXISTS (SELECT 1 FROM recurring_order_item roi WHERE roi.order_id = ro.id)
                    OR EXISTS (SELECT 1 FROM recurring_order_extra_item roei WHERE roei.order_id = ro.id)
                ORDER BY name, ro.day_of_week
            ')
            ->fetchAllAssociative();

        return array_map(static function (array $row): array {
            /** @var array{user_id: int|string, day_of_week: int|string, name: null|string, preferred_place_id: null|int, allow_wholesale: int|string, has_delivery_address: int|string} $row */

            return [
                'user_id' => (int) $row['user_id'],
                'day_of_week' => (int) $row['day_of_week'],
                'name' => (string) $row['name'],
                'preferred_place_id' => $row['preferred_place_id'],
                'is_wholesale' => (bool) $row['allow_wholesale'],
                'has_delivery_address' => (bool) $row['has_delivery_address'],
            ];
        }, $rows);
    }

    /**
     * Skipped deliveries: skip_until = the skipped delivery day (`OrderingDeadline::nextOrderDay`).
     *
     * @return array<string, true> keyed `{userId}_{Y-m-d}`
     */
    public function skippedDays(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT user_id, DATE(skip_until) AS day FROM recurring_order_skip
                WHERE skip_until >= :from AND skip_until < :to
            ', ['from' => $from->format('Y-m-d'), 'to' => $to->modify('+1 day')->format('Y-m-d')])
            ->fetchAllAssociative();

        $skipped = [];

        foreach ($rows as $row) {
            /** @var array{user_id: int|string, day: string} $row */
            $skipped[$row['user_id'] . '_' . $row['day']] = true;
        }

        return $skipped;
    }

    /**
     * Existing orders of the users in the range (any source – also orders placed manually count).
     *
     * @param list<int> $userIds
     * @return array<string, int> order id keyed `{userId}_{Y-m-d}`
     */
    public function existingOrders(array $userIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = $this->connection
            ->executeQuery('
                SELECT user_id, date, MAX(id) AS id FROM `order`
                WHERE del_flag = 0 AND date >= :from AND date <= :to AND user_id IN (:userIds)
                GROUP BY user_id, date
            ', [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'userIds' => $userIds,
            ], ['userIds' => ArrayParameterType::INTEGER])
            ->fetchAllAssociative();

        $orders = [];

        foreach ($rows as $row) {
            /** @var array{user_id: int|string, date: string, id: int|string} $row */
            $orders[$row['user_id'] . '_' . $row['date']] = (int) $row['id'];
        }

        return $orders;
    }
}
