<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin\Order;

use CeskaKruta\Web\Services\OrderPriceCalculator;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\Order\AdminOrderProduct;
use CeskaKruta\Web\Value\Admin\Order\OrderPrice;
use CeskaKruta\Web\Value\Admin\Order\PricedOrderItem;
use CeskaKruta\Web\Value\Price;

/**
 * Prices of an edited order, the same rules as the shop (`Product::price`, `OrderPriceCalculator`). Pure.
 */
final class AdminOrderPricing
{
    /**
     * Web unit price: `product.price` minus the coupon %, whole turkeys per piece = per kg × average weight of the class
     * (halves already carry the halved weights). NULL = cannot be priced (no price, turkey without weight class).
     */
    public static function unitPrice(AdminOrderProduct $product, null|int $couponPercent, null|float $weightFrom, null|float $weightTo): null|int
    {
        if ($product->price === null) {
            return null;
        }

        $pricePerUnit = $product->price;

        if ($couponPercent !== null && $couponPercent > 0) {
            $pricePerUnit = (int) round($pricePerUnit * (100 - $couponPercent) / 100);
        }

        if ($product->isTurkey()) {
            if ($weightFrom === null || $weightTo === null) {
                return null;
            }

            return (int) round($pricePerUnit * ($weightFrom + $weightTo) / 2);
        }

        return $pricePerUnit;
    }

    /**
     * Unit price before the coupon discount, derived back from the stored (discounted) price.
     */
    public static function beforeDiscount(int $pricePerUnit, null|int $couponPercent): int
    {
        if ($couponPercent === null || $couponPercent <= 0 || $couponPercent >= 100) {
            return $pricePerUnit;
        }

        return (int) round($pricePerUnit * 100 / (100 - $couponPercent));
    }

    /**
     * @param list<PricedOrderItem> $items
     */
    public static function orderPrice(array $items, AdminOrderPlace $place): OrderPrice
    {
        $itemsTotal = 0;
        $itemsTotalBeforeDiscount = 0;

        foreach ($items as $item) {
            $itemsTotal += $item->priceTotal() + $item->packingPrice();
            $itemsTotalBeforeDiscount += $item->priceTotalBeforeDiscount() + $item->packingPrice();
        }

        $isDelivery = $place->forceDeliveryAddress;
        $isOwnDelivery = !$place->isCoolBalik;
        $beforeDiscount = new Price($itemsTotalBeforeDiscount);
        $hasFees = $isDelivery && $itemsTotal > 0;

        return new OrderPrice(
            itemsTotal: $itemsTotal,
            itemsTotalBeforeDiscount: $itemsTotalBeforeDiscount,
            packagingPrice: $hasFees ? OrderPriceCalculator::getPackingPrice() : 0,
            deliveryPrice: $hasFees ? OrderPriceCalculator::getDeliveryPriceFor($beforeDiscount, $isDelivery, $isOwnDelivery) : 0,
            isDelivery: $isDelivery,
            isFreeDelivery: OrderPriceCalculator::isFreeDeliveryFor($beforeDiscount, $isDelivery, $isOwnDelivery),
        );
    }
}
