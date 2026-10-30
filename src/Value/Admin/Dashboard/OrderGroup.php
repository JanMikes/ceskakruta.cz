<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

use CeskaKruta\Web\Value\Admin\AdminOrderPlace;

/**
 * Orders aggregated per (`order.date`, `place_id`) – the smallest grouping that still allows mapping to the expedition day.
 */
readonly final class OrderGroup
{
    public function __construct(
        public \DateTimeImmutable $customerDate,
        public AdminOrderPlace $place,
        public SalesChannel $channel,
        public int $ordersCount,
        public float $revenue,
        public int $withNoteCount,
        public int $pohodaMissingCount,
        public int $boxxiMissingCount,
    ) {
    }
}
