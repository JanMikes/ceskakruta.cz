<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\CartItem;
use CeskaKruta\Web\Value\Price;
use CeskaKruta\Web\Value\ProductInCart;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\RequestStack;

final class CartStorage
{
    private const PICKUP_PLACE_SESSION_NAME = 'pickup_place';
    private const ITEMS_SESSION_NAME = 'cart_items';
    private const LOCKED_WEEK_SESSION_NAME = 'locked_week';
    private const DELIVERY_ADDRESS_SESSION_NAME = 'delivery_address';
    private const DATE_SESSION_NAME = 'date';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly GetProducts $getProducts,
    ) {
    }

    public function itemsCount(): int
    {
        return count($this->getItems());
    }

    public function totalPrice(): Price
    {
        $totalPrice = new Price(0);
        $products = $this->getProducts->all(); // TODO: place

        foreach ($this->getItems() as $item) {
            $product = $products[$item->product->id];

            $totalPrice = $totalPrice->add($product->price());
        }

        return $totalPrice;
    }

    public function addItem(CartItem $item): void
    {
        $session = $this->requestStack->getSession();

        /** @var array<mixed> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        // TODO: stacking

        $items[] = $item->toArray();

        $session->set(self::ITEMS_SESSION_NAME, $items);
    }

    /**
     * @return list<ProductInCart>
     */
    public function getItems(): array
    {
        $session = $this->requestStack->getSession();
        $products = $this->getProducts->all($this->getPickupPlace());
        $cart = [];

        /** @var list<array{product_id: int}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $itemData) {
            $cartItem = CartItem::fromArray($itemData);

            // TODO: real quantity
            $cart[] = new ProductInCart(1, $products[$cartItem->productId]);
        }

        return $cart;
    }

    public function clear(): void
    {
        $this->requestStack->getSession()
            ->clear();
    }

    public function removeItem(int $keyToRemove): void
    {
        $session = $this->requestStack->getSession();

        /** @var list<array{product_id: int}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $key => $itemData) {
            if ($keyToRemove === 1 + $key) {
                unset($items[$key]);

                $session->set(self::ITEMS_SESSION_NAME, array_values($items));

                return;
            }
        }
    }

    public function storePickupPlace(null|int $placeId): void
    {
        $this->requestStack->getSession()
            ->set(self::PICKUP_PLACE_SESSION_NAME, $placeId);
    }

    public function getPickupPlace(): null|int
    {
        /** @var null|int $pickupPlace */
        $pickupPlace = $this->requestStack->getSession()
            ->get(self::PICKUP_PLACE_SESSION_NAME);

        return $pickupPlace;
    }

    public function storeDeliveryAddress(null|Address $address): void
    {
        // TODO: address to array

        $this->requestStack->getSession()
            ->set(self::DELIVERY_ADDRESS_SESSION_NAME, null);
    }

    public function getDeliveryAddress(): null|Address
    {
        /** @var null|mixed $sessionData */
        $sessionData = $this->requestStack->getSession()
            ->get(self::DELIVERY_ADDRESS_SESSION_NAME);

        // TODO: address from array

        if ($sessionData !== null) {
            return new Address();
        }

        return null;
    }

    public function storeLockedWeek(null|int $week): void
    {
        $this->requestStack->getSession()
            ->set(self::LOCKED_WEEK_SESSION_NAME, $week);
    }

    public function getLockedWeek(): null|int
    {
        /** @var null|int $lockedWeek */
        $lockedWeek = $this->requestStack->getSession()
            ->get(self::LOCKED_WEEK_SESSION_NAME);

        return $lockedWeek;
    }

    public function storeDate(null|DateTimeImmutable $date): void
    {
        $this->requestStack->getSession()
            ->set(self::DATE_SESSION_NAME, $date?->format('Y-m-d'));
    }

    public function getDate(): null|DateTimeImmutable
    {
        /** @var null|string $date */
        $date = $this->requestStack->getSession()
            ->get(self::DATE_SESSION_NAME);

        if ($date !== null) {
            return DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;
        }

        return null;
    }
}
