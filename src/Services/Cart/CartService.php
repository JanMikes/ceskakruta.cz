<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Cart;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\Price;
use CeskaKruta\Web\Value\ProductInCart;
use CeskaKruta\Web\Value\Week;
use DateTimeImmutable;

readonly final class CartService
{
    public function __construct(
        private CartStorage $storage,
        private GetProducts $getProducts,
        private GetAvailableDays $getAvailableDays,
    ) {
    }

    public function itemsCount(): int
    {
        return $this->storage->itemsCount();
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
        $placeId = $this->getPickupPlace(); // TODO delivery

        if ($date === null || $placeId === null) {
            return null;
        }

        if ($this->getAvailableDays->isDateAvailable($date, $placeId) === false) {
            return null;
        }

        return $date;
    }

    public function totalPrice(): Price
    {
        $totalPrice = new Price(0);
        $products = $this->getProducts->all();

        foreach ($this->storage->getItems() as $item) {
            $product = $products[$item->productId];
            $unitPrice = $product->price();

            $totalPrice = $totalPrice->add((int) ($item->quantity * $unitPrice));

            if ($item->pack === true) {
                $totalPrice = $totalPrice->add($product->packPrice ?? 0);
            }
        }

        return $totalPrice;
    }

    /**
     * @return list<ProductInCart>
     */
    public function getItems(): array
    {
        $products = $this->getProducts->all();
        $items = [];

        foreach ($this->storage->getItems() as $item) {
            $items[] = new ProductInCart(
                $item->quantity,
                $products[$item->productId],
                $item->slice,
                $item->pack,
            );
        }

        return $items;
    }

    public function getOrderData(): null|OrderFormData
    {
        return $this->storage->getOrderData();
    }

    public function getLockedWeek(): null|Week
    {
        return $this->storage->getLockedWeek();
    }
}