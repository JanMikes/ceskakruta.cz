<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Orders created (`order.ins_dt`) in a period: count, revenue (`order.price_total`) and new registrations.
 */
readonly final class PeriodTotals
{
    public function __construct(
        public int $ordersCount,
        public float $revenue,
        public int $registrations = 0,
    ) {
    }

    public function averageOrder(): float
    {
        return $this->ordersCount > 0 ? $this->revenue / $this->ordersCount : 0.0;
    }
}
