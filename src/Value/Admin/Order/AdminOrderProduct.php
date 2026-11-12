<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Order;

/**
 * Product as needed to add / price an order item in the admin order editing.
 */
readonly final class AdminOrderProduct
{
    public function __construct(
        public int $id,
        public string $name,
        public string $unit,
        /** `product.price` per unit (whole turkeys: per kg), NULL = not for sale */
        public null|int $price,
        /** `product.price_pack`, added once per packed item */
        public null|int $pricePack,
        public null|int $type,
        public null|int $halfOfProductId,
        public bool $canBeSliced,
        public bool $canBePacked,
        public bool $active,
    ) {
    }

    public function isTurkey(): bool
    {
        return $this->type === 3 || $this->type === 4;
    }

    /**
     * `order_item.product_tp_id` / `calendar_cold.product_tp_id`: 1 = krůta, 2 = krocan.
     */
    public function turkeyTypeId(): null|int
    {
        return match ($this->type) {
            3 => 1,
            4 => 2,
            default => null,
        };
    }

    public function isHalf(): bool
    {
        return $this->halfOfProductId !== null;
    }
}
