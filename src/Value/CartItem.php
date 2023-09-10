<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

readonly final class CartItem
{
    public function __construct(
        public UuidInterface $productVariantId,
    ) {
    }


    /**
     * @return array{variant_id: string}
     */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->productVariantId->toString(),
        ];
    }


    /**
     * @param array{variant_id: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new CartItem(Uuid::fromString($data['variant_id']));
    }


    public function isSame(CartItem $other): bool
    {
        return $this->toArray() === $other->toArray();
    }
}
