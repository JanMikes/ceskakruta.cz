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
        public null|bool $slice = null,
        public null|bool $pack = null,
        public null|string $note = null,
    ) {
    }


    /**
     * @return array{product_id: int, quantity: int|float, slice: null|bool, pack: null|bool, note: null|string}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'slice' => $this->slice,
            'pack' => $this->pack,
            'note' => $this->note,
        ];
    }


    /**
     * @param array{product_id: int, quantity?: int|float, slice?: null|bool, pack?: null|bool, note?: null|string} $data
     */
    public static function fromArray(array $data): self
    {
        return new CartItem(
            productId: $data['product_id'],
            quantity: $data['quantity'] ?? 1,
            slice: $data['slice'] ?? null,
            pack: $data['pack'] ?? null,
            note: $data['note'] ?? null,
        );
    }

    public function isSameProduct(CartItem $other): bool
    {
        return $this->productId === $other->productId
            && $this->slice === $other->slice
            && $this->pack === $other->pack;
    }

    public function change(int|float $newQuantity, null|string $note = null): self
    {
        return new self(
            $this->productId,
            $newQuantity,
            $this->slice,
            $this->pack,
            $note,
        );
    }
}
