<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Value\Coupon;
use CeskaKruta\Web\Value\Place;
use CeskaKruta\Web\Value\Price;
use CeskaKruta\Web\Value\ProductInCart;

readonly final class OrderPriceCalculator
{
    /**
     * @param array<ProductInCart> $items
     */
    public static function totalPrice(array $items, null|Coupon $coupon, null|Place $place): Price
    {
        $totalPrice = new Price(0);
        $totalItemsPriceWithoutDiscount = self::totalItemsPriceWithoutDiscount($items);

        foreach ($items as $item) {
            $product = $item->product;
            $totalPrice = $totalPrice->add($item->price($coupon));

            if ($item->pack === true) {
                $totalPrice = $totalPrice->add($product->packPrice ?? 0);
            }
        }

        if ($totalPrice->amount > 0 && $place?->isDelivery === true) {
            $totalPrice = $totalPrice->add(self::getPackingPrice());

            if (self::isFreeDelivery($totalItemsPriceWithoutDiscount, $place) === false) {
                $totalPrice = $totalPrice->add(self::getDeliveryPrice($totalItemsPriceWithoutDiscount, $place));
            }
        }

        return $totalPrice;
    }

    /**
     * @param array<ProductInCart> $items
     */
    public static function totalItemsPriceWithoutDiscount(array $items): Price
    {
        $itemsPrice = new Price(0);

        foreach ($items as $item) {
            $product = $item->product;
            $itemsPrice = $itemsPrice->add($item->price());

            if ($item->pack === true) {
                $itemsPrice = $itemsPrice->add($product->packPrice ?? 0);
            }
        }

        return $itemsPrice;
    }

    public static function isFreeDelivery(Price $totalItemsPriceWithoutDiscount, null|Place $deliveryPlace): bool
    {
        return self::isFreeDeliveryFor($totalItemsPriceWithoutDiscount, $deliveryPlace->isDelivery ?? false, $deliveryPlace->isOwnDelivery ?? false);
    }

    /**
     * Same rule from the place flags (admin order editing has no `Place` value): pick-up is free, CoolBalík never,
     * own delivery over 5 000 Kč.
     */
    public static function isFreeDeliveryFor(Price $totalItemsPriceWithoutDiscount, bool $isDelivery, bool $isOwnDelivery): bool
    {
        if ($isDelivery === false) {
            return true;
        }

        if ($isOwnDelivery === false) {
            return false;
        }

        return $totalItemsPriceWithoutDiscount->amount > 5000;
    }

    public static function getDeliveryPrice(Price $totalItemsPriceWithoutDiscount, null|Place $deliveryPlace): int
    {
        return self::getDeliveryPriceFor($totalItemsPriceWithoutDiscount, $deliveryPlace->isDelivery ?? false, $deliveryPlace->isOwnDelivery ?? false);
    }

    public static function getDeliveryPriceFor(Price $totalItemsPriceWithoutDiscount, bool $isDelivery, bool $isOwnDelivery): int
    {
        if (self::isFreeDeliveryFor($totalItemsPriceWithoutDiscount, $isDelivery, $isOwnDelivery)) {
            return 0;
        }

        return 195;
    }

    public static function getPackingPrice(): int
    {
        return 35;
    }
}
