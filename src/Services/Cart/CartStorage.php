<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
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
    private const CUSTOMER_SESSION_NAME = 'customer';
    private const ORDER_ID_SESSION_NAME = 'order_id';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly GetProducts $getProducts,
        private readonly GetColdProductsCalendar $getColdProductsCalendar,
    ) {
    }

    public function itemsCount(): int
    {
        return count($this->getItems());
    }

    public function totalPrice(): Price
    {
        $totalPrice = new Price(0);
        $products = $this->getProducts->all(
            $this->getPickupPlace(), // TODO: might be delivery
        );
        $calendar = $this->getColdProductsCalendar->all();
        $calendarWeek = $this->getWeek();
        $week = $calendarWeek['week'];
        $year = $calendarWeek['year'];

        foreach ($this->getItems() as $item) {
            $product = $products[$item->product->id];

            $price = $product->price();

            if ($product->type === 3) {
                $weights = $calendar[$year][$week][$product->turkeyType];
                $price = ($weights->weightFrom + $weights->weightTo) / 2 * $product->price();
            }

            $totalPrice = $totalPrice->add((int) ($item->quantity * $price));
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
        $products = $this->getProducts->all($this->getPickupPlace()); // TODO
        $cart = [];

        /** @var list<array{product_id: int}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $itemData) {
            $cartItem = CartItem::fromArray($itemData);

            $cart[] = new ProductInCart(
                $cartItem->quantity,
                $products[$cartItem->productId],
            );
        }

        return $cart;
    }

    public function clear(): void
    {
        $session = $this->requestStack->getSession();

        $session->remove(self::ITEMS_SESSION_NAME);
        $session->remove(self::DATE_SESSION_NAME);
        $session->remove(self::LOCKED_WEEK_SESSION_NAME);
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

    public function storeLockedWeek(null|int $year, null|int $week): void
    {
        $data = null;

        if ($week !== null && $year !== null) {
            $data = [
                'week' => $week,
                'year' => $year,
            ];
        }

        $this->requestStack->getSession()
            ->set(self::LOCKED_WEEK_SESSION_NAME, $data);
    }

    /**
     * @return null|array{week: int, year: int}
     */
    public function getLockedWeek(): null|array
    {
        /** @var null|array{week: int, year: int} $lockedWeek */
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

    public function storeOrderData(null|OrderFormData $orderData): void
    {
        $data = null;

        if ($orderData !== null) {
            $data = [
                'name' => $orderData->name,
                'email' => $orderData->email,
                'phone' => $orderData->phone,
                'note' => $orderData->note,
                'subscribeToNewsletter' => $orderData->subscribeToNewsletter,
            ];
        }

        $this->requestStack->getSession()
            ->set(self::CUSTOMER_SESSION_NAME, $data);
    }

    public function getOrderData(): null|OrderFormData
    {
        /**
         * @var null|array{
         *     name?: string,
         *     email?: string,
         *     phone?: string,
         *     note?: string,
         *     subscribeToNewsletter?: bool,
         * } $data
         */
        $data = $this->requestStack->getSession()
            ->get(self::CUSTOMER_SESSION_NAME);

        if ($data !== null) {
            $orderData = new OrderFormData();
            $orderData->name = $data['name'] ?? '';
            $orderData->email = $data['email'] ?? '';
            $orderData->phone = $data['phone'] ?? '';
            $orderData->note = $data['note'] ?? '';
            $orderData->subscribeToNewsletter = $data['subscribeToNewsletter'] ?? false;
            return $orderData;
        }

        return null;
    }

    public function storeLastOrderId(null|int $orderId): void
    {
        $this->requestStack->getSession()
            ->set(self::ORDER_ID_SESSION_NAME, $orderId);
    }

    public function getLastOrderId(): null|int
    {
        /** @var null|int $lastOrderId */
        $lastOrderId = $this->requestStack->getSession()
            ->get(self::ORDER_ID_SESSION_NAME);

        return $lastOrderId;
    }

    /**
     * @return array{week: int, year: int}
     */
    public function getWeek(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'week' => (int) $now->format('W'),
            'year' => (int) $now->format('Y'),
        ];
    }
}
