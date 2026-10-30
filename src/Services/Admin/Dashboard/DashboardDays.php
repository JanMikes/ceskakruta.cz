<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin\Dashboard;

use CeskaKruta\Web\Query\Admin\GetDashboardOrders;
use CeskaKruta\Web\Services\Pohoda\PohodaDates;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\Dashboard\DateBasis;
use CeskaKruta\Web\Value\Admin\Dashboard\DayOrder;
use CeskaKruta\Web\Value\Admin\Dashboard\DayStats;
use CeskaKruta\Web\Value\Admin\Dashboard\PrepItem;
use CeskaKruta\Web\Value\Admin\Dashboard\SyncAttention;

/**
 * Orders per day for the dashboard operations part, by expedition day (Pohoda `dateTo`, the same rule the Pohoda export
 * uses) or by customer day (`order.date`). The expedition day is never after the customer day and at most
 * `EXPEDITION_LEAD_DAYS` before it (CoolBalík Sunday → last Tuesday), so the customer day range is widened by that much.
 */
readonly final class DashboardDays
{
    public const EXPEDITION_LEAD_DAYS = 6;

    /** Unsynced orders older than this (by expedition day) are not reported any more */
    public const SYNC_LOOKBACK_DAYS = 14;

    public function __construct(
        private GetDashboardOrders $getDashboardOrders,
        private PohodaDates $pohodaDates,
    ) {
    }

    /**
     * Every day of the range is present (empty days included), keyed by `Y-m-d`.
     *
     * @return array<string, DayStats>
     */
    public function days(DateBasis $basis, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $days = [];

        for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
            $days[$day->format('Y-m-d')] = new DayStats($day);
        }

        foreach ($this->getDashboardOrders->groups($from, $this->customerTo($basis, $to)) as $group) {
            $key = $this->day($basis, $group->customerDate, $group->place)->format('Y-m-d');

            if (isset($days[$key])) {
                $days[$key]->add($group);
            }
        }

        return $days;
    }

    /**
     * What to prepare for one day: products summed over all its orders, sorted by name.
     *
     * @return list<PrepItem>
     */
    public function prepItems(DateBasis $basis, \DateTimeImmutable $day): array
    {
        $items = [];

        foreach ($this->getDashboardOrders->items($day, $this->customerTo($basis, $day)) as $row) {
            if ($this->day($basis, $row['date'], $row['place'])->format('Y-m-d') !== $day->format('Y-m-d')) {
                continue;
            }

            $item = $items[$row['product_id']] ??= new PrepItem($row['product_id'], $row['product_name'], $row['unit']);
            $item->amount += $row['amount'];
            $item->slicedAmount += $row['sliced_amount'];
            $item->packedAmount += $row['packed_amount'];
            $item->ordersCount += $row['orders_count'];
        }

        usort($items, static fn (PrepItem $a, PrepItem $b): int => strcoll($a->name, $b->name));

        return $items;
    }

    /**
     * @return list<DayOrder>
     */
    public function orders(DateBasis $basis, \DateTimeImmutable $day): array
    {
        $orders = [];

        foreach ($this->getDashboardOrders->orders($day, $this->customerTo($basis, $day)) as $row) {
            $expeditionDate = $this->pohodaDates->dateTo($row['date'], $row['place']);
            $orderDay = $basis === DateBasis::Expedition ? $expeditionDate : $row['date'];

            if ($orderDay->format('Y-m-d') !== $day->format('Y-m-d')) {
                continue;
            }

            $orders[] = new DayOrder(
                id: $row['id'],
                name: $row['name'],
                placeName: $row['place']->name,
                channel: $row['channel'],
                customerDate: $row['date'],
                expeditionDate: $expeditionDate,
                priceTotal: $row['price_total'],
                hasNote: $row['has_note'],
                pohodaSynced: $row['pohoda_synced'],
                boxxiSynced: $row['place']->isCoolBalik ? $row['boxxi_synced'] : null,
            );
        }

        return $orders;
    }

    /**
     * Orders not yet sent to Pohoda / Boxxi, always by expedition day (that is when it is too late): overdue = expedition
     * today or earlier (within `SYNC_LOOKBACK_DAYS`), upcoming = later, including orders made far ahead.
     */
    public function syncAttention(\DateTimeImmutable $today): SyncAttention
    {
        $since = $today->modify(sprintf('-%d days', self::SYNC_LOOKBACK_DAYS));
        $attention = new SyncAttention($since);

        // Expedition day <= customer day, so customer days from `$since` cover all expedition days from `$since`
        foreach ($this->getDashboardOrders->groups($since, null) as $group) {
            if ($group->pohodaMissingCount === 0 && $group->boxxiMissingCount === 0) {
                continue;
            }

            $expedition = $this->pohodaDates->dateTo($group->customerDate, $group->place);

            if ($expedition < $since) {
                continue;
            }

            $attention->add($group, $expedition <= $today);
        }

        return $attention;
    }

    private function day(DateBasis $basis, \DateTimeImmutable $customerDate, AdminOrderPlace $place): \DateTimeImmutable
    {
        return $basis === DateBasis::Expedition ? $this->pohodaDates->dateTo($customerDate, $place) : $customerDate;
    }

    private function customerTo(DateBasis $basis, \DateTimeImmutable $to): \DateTimeImmutable
    {
        return $basis === DateBasis::Expedition ? $to->modify(sprintf('+%d days', self::EXPEDITION_LEAD_DAYS)) : $to;
    }
}
