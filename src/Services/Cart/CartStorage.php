<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\Value\CartItem;

interface CartStorage
{
    public function addItem(CartItem $item): void;

    /**
     * @return list<CartItem>
     */
    public function getItems(): array;

    public function removeItem(CartItem $itemToRemove): void;

    public function clear(): void;
}
