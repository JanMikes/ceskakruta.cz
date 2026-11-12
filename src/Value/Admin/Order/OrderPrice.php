<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Order;

/**
 * Order total by the web rules (`OrderPriceCalculator`): items + packed items' packing + delivery fees.
 */
readonly final class OrderPrice
{
    public function __construct(
        /** Items incl. packing of packed items, after the coupon discount */
        public int $itemsTotal,
        public int $itemsTotalBeforeDiscount,
        /** "Balné" of a delivery order, 0 for pick-up */
        public int $packagingPrice,
        /** "Doprava", 0 for pick-up and free own delivery */
        public int $deliveryPrice,
        public bool $isDelivery,
        public bool $isFreeDelivery,
    ) {
    }

    /**
     * `order.price_total`
     */
    public function total(): int
    {
        return $this->itemsTotal + $this->packagingPrice + $this->deliveryPrice;
    }

    /**
     * `order.price_total_before_discount` (set only for orders with a coupon)
     */
    public function totalBeforeDiscount(): int
    {
        return $this->itemsTotalBeforeDiscount + $this->packagingPrice + $this->deliveryPrice;
    }
}
