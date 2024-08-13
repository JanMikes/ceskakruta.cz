<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class ProductInCart
{
    public function __construct(
        public float|int $quantity,
        public Product $product,
        public null|bool $slice = null,
        public null|bool $pack = null,
        public null|string $note = null,
    ) {
    }

    public function price(null|Coupon $coupon = null): int
    {
        return (int) round($this->quantity * $this->product->price($coupon));
    }
}
