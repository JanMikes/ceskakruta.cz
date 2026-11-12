<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * One product (and ordered weight class, whole turkeys only) to prepare for a day: amount summed over all orders of
 * the day (in the product unit).
 */
final class PrepItem
{
    /** `product.type` of whole / half turkeys: 3 = krůta, 4 = krocan */
    public const TURKEY_TYPES = [3 => 'Krůta', 4 => 'Krocan'];

    public float $amount = 0.0;
    public float $slicedAmount = 0.0;
    public float $packedAmount = 0.0;
    public int $ordersCount = 0;

    public function __construct(
        readonly public int $productId,
        readonly public string $name,
        readonly public string $unit,
        readonly public null|int $type = null,
        /** Set = this product is the half of that whole product */
        readonly public null|int $halfOfProductId = null,
        /** Weight class ordered (`order_item.weight_from` / `weight_to`, whole turkeys) */
        readonly public null|float $weightFrom = null,
        readonly public null|float $weightTo = null,
    ) {
    }

    public static function key(int $productId, null|float $weightFrom, null|float $weightTo): string
    {
        return $productId . '|' . $weightFrom . '|' . $weightTo;
    }

    public function isTurkey(): bool
    {
        return $this->type !== null && isset(self::TURKEY_TYPES[$this->type]);
    }

    public function isHalf(): bool
    {
        return $this->halfOfProductId !== null;
    }
}
