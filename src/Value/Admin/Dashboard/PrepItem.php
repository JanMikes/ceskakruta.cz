<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * One product to prepare for a day: amount summed over all orders of the day (in the product unit).
 */
final class PrepItem
{
    public float $amount = 0.0;
    public float $slicedAmount = 0.0;
    public float $packedAmount = 0.0;
    public int $ordersCount = 0;

    public function __construct(
        readonly public int $productId,
        readonly public string $name,
        readonly public string $unit,
    ) {
    }
}
