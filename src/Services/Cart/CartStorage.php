<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Services\Calendar;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\CartItem;
use CeskaKruta\Web\Value\Week;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\RequestStack;

final class CartStorage
{
    private const PICKUP_PLACE_SESSION_NAME = 'pickup_place';
    private const DELIVERY_PLACE_SESSION_NAME = 'delivery_place';
    private const ITEMS_SESSION_NAME = 'cart_items';
    private const LOCKED_WEEK_SESSION_NAME = 'locked_week';
    private const DELIVERY_ADDRESS_SESSION_NAME = 'delivery_address';
    private const DATE_SESSION_NAME = 'date';
    private const CUSTOMER_SESSION_NAME = 'customer';
    private const ORDER_ID_SESSION_NAME = 'order_id';
    private const COUPON_SESSION_NAME = 'coupon';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Calendar $calendar,
    ) {
    }

    public function itemsCount(): int
    {
        return count($this->getItems());
    }

    public function addItem(CartItem $item): void
    {
        $session = $this->requestStack->getSession();
        $addNew = true;

        /** @var array<array{product_id: int, quantity?: int|float, slice?: null|bool, pack?: null|bool}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $key => $existingItem) {
            $existingCartItem = CartItem::fromArray($existingItem);

            if ($existingCartItem->isSameProduct($item)) {
                $newQuantity = $existingCartItem->quantity + $item->quantity;
                $items[$key] = $existingCartItem->withQuantity($newQuantity)->toArray();
                $addNew = false;
            }
        }

        if ($addNew === true) {
            $items[] = $item->toArray();
        }

        $session->set(self::ITEMS_SESSION_NAME, $items);
    }

    /**
     * @return list<CartItem>
     */
    public function getItems(): array
    {
        $session = $this->requestStack->getSession();
        $cart = [];

        /** @var list<array{product_id: int, quantity: int|float, slice?: null|bool, pack?: null|bool}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $itemData) {
            $cart[] = CartItem::fromArray($itemData);
        }

        return $cart;
    }

    public function clearItems(): void
    {
        $session = $this->requestStack->getSession();

        $session->remove(self::ITEMS_SESSION_NAME);
    }

    public function clear(): void
    {
        $session = $this->requestStack->getSession();

        $session->remove(self::ITEMS_SESSION_NAME);
        $session->remove(self::DATE_SESSION_NAME);
        $session->remove(self::LOCKED_WEEK_SESSION_NAME);
        $session->remove(self::COUPON_SESSION_NAME);
    }

    public function removeItem(int $keyToRemove): void
    {
        $session = $this->requestStack->getSession();

        /** @var list<array{product_id: int}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $key => $itemData) {
            if ($keyToRemove === $key) {
                unset($items[$key]);

                $session->set(self::ITEMS_SESSION_NAME, array_values($items));

                return;
            }
        }
    }

    public function changeQuantity(int $keyToChange, int|float $newQuantity): void
    {
        if ($newQuantity < 0) {
            $this->removeItem($keyToChange);
            return;
        }

        $session = $this->requestStack->getSession();

        /** @var array<array{product_id: int, quantity?: int|float, slice?: null|bool, pack?: null|bool}> $items */
        $items = $session->get(self::ITEMS_SESSION_NAME, []);

        foreach ($items as $key => $item) {
            if ($keyToChange === $key) {
                $cartItem = CartItem::fromArray($item);
                $items[$key] = $cartItem->withQuantity($newQuantity)->toArray();

                $session->set(self::ITEMS_SESSION_NAME, $items);
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

    public function storeDeliveryPlace(null|int $placeId): void
    {
        $this->requestStack->getSession()
            ->set(self::DELIVERY_PLACE_SESSION_NAME, $placeId);
    }

    public function getDeliveryPlace(): null|int
    {
        /** @var null|int $deliveryPlace */
        $deliveryPlace = $this->requestStack->getSession()
            ->get(self::DELIVERY_PLACE_SESSION_NAME);

        return $deliveryPlace;
    }

    public function storeDeliveryAddress(null|Address $address): void
    {
        $data = $address?->toArray();

        $this->requestStack->getSession()
            ->set(self::DELIVERY_ADDRESS_SESSION_NAME, $data);
    }

    public function getDeliveryAddress(): null|Address
    {
        /** @var null|array{street: string, city: string, postalCode: string} $sessionData */
        $sessionData = $this->requestStack->getSession()
            ->get(self::DELIVERY_ADDRESS_SESSION_NAME);

        if ($sessionData !== null) {
            return Address::fromArray($sessionData);
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

        // Because choosing week -> remove the chosen date
        $this->storeDate(null);
    }

    public function getLockedWeek(): null|Week
    {
        /** @var null|array{week: int, year: int} $lockedWeek */
        $lockedWeek = $this->requestStack->getSession()
            ->get(self::LOCKED_WEEK_SESSION_NAME);

        if ($lockedWeek !== null) {
            return new Week(
                number: $lockedWeek['week'],
                year: $lockedWeek['year'],
            );
        }

        return null;
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

    public function storeCoupon(null|string $coupon): void
    {
        $this->requestStack->getSession()
            ->set(self::COUPON_SESSION_NAME, $coupon);
    }

    public function getCoupon(): null|string
    {
        /** @var null|string $coupon */
        $coupon = $this->requestStack->getSession()
            ->get(self::COUPON_SESSION_NAME);

        return $coupon;
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

    public function getWeek(): Week
    {
        $lockedWeek = $this->getLockedWeek();

        if ($lockedWeek !== null) {
            return $lockedWeek;
        }

        $date = $this->getDate();

        if ($date === null) {
            return $this->calendar->getCurrentWeek();
        }

        return new Week(
            number: (int) $date->format('W'),
            year: (int) $date->format('Y'),
        );
    }
}
