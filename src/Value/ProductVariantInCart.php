<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class ProductVariantInCart
{
    public function __construct(
        public int $productId,
    ) {
    }
}
