<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Value\ProductInCart;

final class ProductTypesSorter
{
    /**
     * @param array<ProductInCart> $items
     * @return array{0: array<ProductInCart>, 1: array<ProductInCart>, 2: array<ProductInCart>}
     */
    public static function sort(array $items): array
    {
        $turkeyItems = [];
        $meatTypeItems = [];
        $otherItems = [];

        foreach ($items as $item) {
            if ($item->product->isTurkey) {
                $turkeyItems[] = $item;
            } elseif ($item->product->type === 1) {
                $meatTypeItems[] = $item;
            } else {
                $otherItems[] = $item;
            }
        }

        usort($turkeyItems, static fn(ProductInCart $a, ProductInCart $b): int => $b->product->order <=> $a->product->order);
        usort($meatTypeItems, static fn(ProductInCart $a, ProductInCart $b): int => $b->product->order <=> $a->product->order);
        usort($otherItems, static fn(ProductInCart $a, ProductInCart $b): int => $b->product->order <=> $a->product->order);

        return [$turkeyItems, $meatTypeItems, $otherItems];
    }
}
