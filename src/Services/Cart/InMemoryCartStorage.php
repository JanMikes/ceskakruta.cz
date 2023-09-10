<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\Value\CartItem;

final class InMemoryCartStorage implements CartStorage
{
    public function __construct(
        /** @var list<CartItem> */
        private array $items = [],
    ) {
    }


    public function addItem(CartItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return list<CartItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function removeItem(CartItem $itemToRemove): void
    {
        foreach ($this->items as $key => $itemInCart) {
            if ($itemToRemove->isSame($itemInCart)) {
                unset($this->items[$key]);

                return;
            }
        }
    }
}
