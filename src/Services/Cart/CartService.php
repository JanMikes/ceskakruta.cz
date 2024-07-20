<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\Place;
use CeskaKruta\Web\Value\Price;
use CeskaKruta\Web\Value\ProductInCart;
use CeskaKruta\Web\Value\Week;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

readonly final class CartService
{
    public function __construct(
        private CartStorage $storage,
        private GetProducts $getProducts,
        private GetAvailableDays $getAvailableDays,
        private GetColdProductsCalendar $getColdProductsCalendar,
        private GetPlaces $getPlaces,
    ) {
    }

    public function isMinimalPriceMet(): bool
    {
        return $this->totalPrice()->amount >= $this->getMinimalPrice();
    }

    public function getMinimalPrice(): int
    {
        if ($this->getPlace()?->isDelivery) {
            return 1000;
        }

        return 0;
    }

    public function itemsCount(): int
    {
        return count($this->getItems());
    }

    public function getPickupPlace(): null|int
    {
        return $this->storage->getPickupPlace();
    }

    public function getDeliveryAddress(): null|Address
    {
        return $this->storage->getDeliveryAddress();
    }

    public function getDate(): null|DateTimeImmutable
    {
        $date = $this->storage->getDate();
        $place = $this->getPlace();

        if ($date === null || $place === null) {
            return null;
        }

        if ($this->getAvailableDays->isDateAvailable($date, $place->id) === false) {
            $this->storage->storeDate(null);
            return null;
        }

        return $date;
    }

    public function getPlace(): null|Place
    {
        $deliveryPlace = $this->getDeliveryPlace();

        if ($deliveryPlace !== null) {
            return $this->getPlaces->oneById($deliveryPlace);
        }

        $pickupPlace = $this->getPickupPlace();

        if ($pickupPlace !== null) {
            return $this->getPlaces->oneById($pickupPlace);
        }

        return null;
    }

    /**
     * @return list<ProductInCart>
     */
    public function getItems(): array
    {
        $products = $this->getProducts->all();
        $calendar = $this->getColdProductsCalendar->all();
        $items = [];
        $week = $this->getWeek();

        foreach ($this->storage->getItems() as $key => $item) {
            $product = $products[$item->productId];

            // check calendar, if any of products is not available then remove them
            if ($product->isTurkey === true && ($calendar[$week->year][$week->number][$product->turkeyType] ?? null) === null) {
                $this->storage->removeItem($key);
                continue;
            }

            $items[] = new ProductInCart(
                $item->quantity,
                $product,
                $item->slice,
                $item->pack,
            );
        }

        return $items;
    }

    public function totalPrice(): Price
    {
        $totalPrice = new Price(0);

        foreach ($this->getItems() as $item) {
            $product = $item->product;
            $unitPrice = $product->price();

            $totalPrice = $totalPrice->add((int) ($item->quantity * $unitPrice));

            if ($item->pack === true) {
                $totalPrice = $totalPrice->add($product->packPrice ?? 0);
            }
        }

        if ($totalPrice->amount > 0 && $this->getDeliveryAddress() !== null && $this->getDeliveryPlace() !== null) {
            $totalPrice = $totalPrice->add($this->getDeliveryPrice());
            $totalPrice = $totalPrice->add($this->getPackingPrice());
        }

        return $totalPrice;
    }

    public function getDeliveryPrice(): int
    {
        return 195;
    }

    public function getPackingPrice(): int
    {
        return 35;
    }

    public function getOrderData(): null|OrderFormData
    {
        return $this->storage->getOrderData();
    }

    public function getLockedWeek(): null|Week
    {
        return $this->storage->getLockedWeek();
    }

    public function getWeek(): Week
    {
        return $this->storage->getWeek();
    }

    public function containsTurkey(): bool
    {
        foreach ($this->getItems() as $item) {
            if ($item->product->isTurkey === true) {
                return true;
            }
        }

        return false;
    }

    public function removeAllTurkeys(): void
    {
        foreach ($this->getItems() as $key => $item) {
            if ($item->product->isTurkey === true) {
                $this->storage->removeItem($key);
            }
        }
    }

    public function getDeliveryPlace(): null|int
    {
        return $this->storage->getDeliveryPlace();
    }

    public function removeItem(int $keyToRemove): void
    {
        $this->storage->removeItem($keyToRemove);

        if ($this->containsTurkey() === false) {
            $this->storage->storeLockedWeek(null, null);
        }
    }

    public function isOrderReadyToBePlaced(): bool
    {
        if ($this->getPlace() === null) {
            return false;
        }

        if ($this->getDate() === null) {
            return false;
        }

        if ($this->getOrderData() === null) {
            return false;
        }

        if ($this->storage->itemsCount() <= 0) {
            return false;
        }

        if ($this->isMinimalPriceMet()) {
            return false;
        }

        return true;
    }
}
