<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use CeskaKruta\Web\Entity\RecurringOrderItem;

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

    public static function createFromRecurringOrderItem(RecurringOrderItem $recurringOrderItem, Product $product): self
    {
        $quantity = $recurringOrderItem->calculateQuantityInKg();
        $note = sprintf('%s%s%s',
            trim((string) $recurringOrderItem->note),
            trim((string) $recurringOrderItem->note) !== '' ? ' | ' : '',
            $recurringOrderItem->quantitiesAsNote(),
        );

        return new self(
            quantity: $quantity,
            product: $product,
            slice: $recurringOrderItem->isSliced,
            pack: $recurringOrderItem->isPacked,
            note: $note,
        );
    }

    /**
     * @param array<ProductInCart> $items
     * @return array<ProductInCart>
     */
    public static function sortItemsByType(array $items): array
    {
        usort($items, static function (ProductInCart $a, ProductInCart $b): int {
            $typeA = $a->product->type;
            $typeB = $b->product->type;

            if ($typeA === null && $typeB === null) {
                return 0;
            }

            if ($typeA === null) {
                return 1;
            }

            if ($typeB === null) {
                return -1;
            }

            return $typeA <=> $typeB;
        });

        return $items;
    }
}
