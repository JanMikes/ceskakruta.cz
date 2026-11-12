<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Order item (`order_item` row + its product) as needed by the Pohoda XML, the Boxxi weight calculation and order editing.
 */
readonly final class AdminOrderItem
{
    public function __construct(
        public int $id,
        public int $productId,
        /** `order_item.product_tp_id` (1 = Krůta, 2 = Krocan), set only for whole turkey items */
        public null|int $productTypeId,
        /** `product.name` */
        public string $productName,
        /** `product.code` ("Kód produktu do Pohody") */
        public null|string $productCode,
        /** `product.unit` (kg|ks) */
        public string $productUnit,
        /** `product.weight_per_unit` */
        public float $productWeightPerUnit,
        public float $amount,
        public int $pricePerUnit,
        public null|float $weightFrom,
        public null|float $weightTo,
        public bool $isSliced = false,
        public bool $isPacked = false,
        /** `order_item.price_packing`, added once per item when packed */
        public int $pricePacking = 0,
        public null|string $note = null,
    ) {
    }
}
