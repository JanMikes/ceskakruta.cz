<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class OrderItem
{
    public function __construct(
        public int $productId,
        public bool $isSliced,
        public bool $isPacked,
        public int|float $pricePacking,
        public int|float $amount,
        public int|float $pricePerUnit,
        public int|float $priceTotal,
        public null|float $weightFrom,
        public null|float $weightTo,
        public null|string $note,
    ) {
    }
}
