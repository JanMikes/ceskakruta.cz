<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

readonly final class TopProduct
{
    public function __construct(
        public int $productId,
        public string $name,
        public string $unit,
        public float $amount,
        public float $revenue,
        public int $ordersCount,
    ) {
    }
}
