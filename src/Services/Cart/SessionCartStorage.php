<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\Value\CartItem;
use Symfony\Component\HttpFoundation\RequestStack;

final class SessionCartStorage implements CartStorage
{
    private const SESSION_NAME = 'cart_items';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function addItem(CartItem $item): void
    {
        $session = $this->requestStack->getSession();

        /** @var array<mixed> $items */
        $items = $session->get(self::SESSION_NAME, []);

        $items[] = $item->toArray();

        $session->set(self::SESSION_NAME, $items);
    }

    /**
     * @return list<CartItem>
     */
    public function getItems(): array
    {
        return [];

        $session = $this->requestStack->getSession();

        /** @var list<array{variant_id: string}> $items */
        $items = $session->get(self::SESSION_NAME, []);

        $cart = [];

        foreach ($items as $itemData) {
            $cart[] = CartItem::fromArray($itemData);
        }

        return $cart;
    }

    public function clear(): void
    {
        $this->requestStack->getSession()
            ->clear();
    }

    public function removeItem(CartItem $itemToRemove): void
    {
        $session = $this->requestStack->getSession();

        /** @var list<array{variant_id: string}> $items */
        $items = $session->get(self::SESSION_NAME, []);

        foreach ($items as $key => $itemData) {
            $itemInCart = CartItem::fromArray($itemData);

            if ($itemInCart->isSame($itemToRemove)) {
                unset($items[$key]);
                $session->set(self::SESSION_NAME, $items);

                return;
            }
        }
    }
}
