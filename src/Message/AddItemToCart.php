<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class AddItemToCart
{
    public function __construct(
        public int $productId,
        public int|float $quantity,
        public null|bool $slice,
        public null|bool $pack,
    ) {}
}
