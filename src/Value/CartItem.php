<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

readonly final class CartItem
{
    public function __construct(
        public int $productId,
        public int|float $quantity,
    ) {
    }


    /**
     * @return array{product_id: int, quantity: int|float}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ];
    }


    /**
     * @param array{product_id: int, quantity?: int|float} $data
     */
    public static function fromArray(array $data): self
    {
        return new CartItem(
            $data['product_id'],
            $data['quantity'] ?? 1,
        );
    }


    public function isSame(CartItem $other): bool
    {
        return $this->toArray() === $other->toArray();
    }
}
