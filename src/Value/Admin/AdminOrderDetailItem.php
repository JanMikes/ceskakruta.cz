<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminOrderDetailItem
{
    public function __construct(
        public AdminOrderItem $item,
        /** `order_item.price_total` */
        public int $priceTotal,
    ) {
    }
}
