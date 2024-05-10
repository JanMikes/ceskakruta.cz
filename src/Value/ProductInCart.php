<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class ProductInCart
{
    public function __construct(
        public float|int $quantity,
        public Product $product,
    ) {
    }
}
