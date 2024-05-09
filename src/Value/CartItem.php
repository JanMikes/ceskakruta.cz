<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

readonly final class CartItem
{
    public function __construct(
        public int $productId,
    ) {
    }


    /**
     * @return array{product_id: int}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
        ];
    }


    /**
     * @param array{product_id: int} $data
     */
    public static function fromArray(array $data): self
    {
        return new CartItem($data['product_id']);
    }


    public function isSame(CartItem $other): bool
    {
        return $this->toArray() === $other->toArray();
    }
}
