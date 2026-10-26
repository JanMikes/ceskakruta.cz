<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Boxxi;

use CeskaKruta\Web\Value\Admin\AdminOrderItem;

/**
 * Default weight + box count of the Boxxi form, ported 1:1 from old `libs/services/CoolBalik.php`
 * (`calculateWeight`, `calculateBoxes`). Pure, no dependencies.
 */
final class ShipmentWeightCalculator
{
    /**
     * Σ per item: amount × (weight_from + weight_to) / 2 when both weights are set (whole turkeys),
     * else amount × product.weight_per_unit for `ks` products, else amount (kg).
     *
     * @param list<AdminOrderItem> $items
     */
    public function weight(array $items): float
    {
        $weight = 0.0;

        foreach ($items as $item) {
            if ($item->weightFrom !== null && $item->weightTo !== null) {
                $weight += $item->amount * (($item->weightFrom + $item->weightTo) / 2);
            } elseif ($item->productUnit === 'ks') {
                $weight += $item->amount * $item->productWeightPerUnit;
            } else {
                $weight += $item->amount;
            }
        }

        return $weight;
    }

    /**
     * ceil(weight / 20)
     */
    public function boxes(float $weight): int
    {
        return (int) ceil($weight / 20);
    }
}
