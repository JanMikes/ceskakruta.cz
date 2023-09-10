<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use CeskaKruta\Web\Entity\ProductVariant;

readonly final class ProductVariantInCart
{
    public function __construct(
        public ProductVariant $variant,
    ) {
    }
}
