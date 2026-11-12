<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Order;

/**
 * Order item prices: unit price after the coupon discount (stored as `order_item.price_per_unit`) and before it.
 */
readonly final class PricedOrderItem
{
    public function __construct(
        public int $pricePerUnit,
        public int $pricePerUnitBeforeDiscount,
        public float $amount,
        public bool $isPacked,
        public int $pricePacking,
        /**
         * Unchanged existing item: its stored `price_total` per 1 unit of amount. Old orders may store whole turkeys
         * per kg (total = price × weight), so the stored total is scaled instead of recomputed from the unit price.
         */
        public null|float $storedTotalPerAmount = null,
    ) {
    }

    /**
     * `order_item.price_total` (packing not included, as on the web)
     */
    public function priceTotal(): int
    {
        if ($this->storedTotalPerAmount !== null) {
            return (int) round($this->amount * $this->storedTotalPerAmount);
        }

        return (int) round($this->amount * $this->pricePerUnit);
    }

    public function priceTotalBeforeDiscount(): int
    {
        if ($this->storedTotalPerAmount !== null && $this->pricePerUnit > 0) {
            return (int) round($this->priceTotal() * $this->pricePerUnitBeforeDiscount / $this->pricePerUnit);
        }

        return (int) round($this->amount * $this->pricePerUnitBeforeDiscount);
    }

    public function packingPrice(): int
    {
        return $this->isPacked ? $this->pricePacking : 0;
    }
}
