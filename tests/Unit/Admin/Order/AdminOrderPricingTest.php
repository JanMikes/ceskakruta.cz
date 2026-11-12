<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Admin\Order;

use CeskaKruta\Web\Services\Admin\Order\AdminOrderPricing;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\Order\AdminOrderProduct;
use CeskaKruta\Web\Value\Admin\Order\PricedOrderItem;
use PHPUnit\Framework\TestCase;

final class AdminOrderPricingTest extends TestCase
{
    public function testUnitPriceFollowsTheWeb(): void
    {
        $meat = self::product(price: 289);
        $turkey = self::product(price: 123, type: 3);

        self::assertSame(289, AdminOrderPricing::unitPrice($meat, null, null, null));
        // Coupon first, rounded per unit, as `Product::price`
        self::assertSame(260, AdminOrderPricing::unitPrice($meat, 10, null, null));
        // Whole turkey per piece = per kg × average weight; half carries halved weights
        self::assertSame(984, AdminOrderPricing::unitPrice($turkey, null, 7, 9));
        self::assertSame(492, AdminOrderPricing::unitPrice($turkey, null, 3.5, 4.5));
        self::assertSame(888, AdminOrderPricing::unitPrice($turkey, 10, 7, 9));

        self::assertNull(AdminOrderPricing::unitPrice($turkey, null, null, null), 'Turkey without weight class');
        self::assertNull(AdminOrderPricing::unitPrice(self::product(price: null), null, null, null), 'No price');
    }

    public function testBeforeDiscount(): void
    {
        self::assertSame(289, AdminOrderPricing::beforeDiscount(260, 10));
        self::assertSame(260, AdminOrderPricing::beforeDiscount(260, null));
    }

    public function testPickUpHasNoFees(): void
    {
        $price = AdminOrderPricing::orderPrice([self::item(289, 2, packed: true, packing: 15)], self::place(delivery: false, coolBalik: false));

        self::assertSame(593, $price->total());
        self::assertSame(0, $price->packagingPrice);
        self::assertSame(0, $price->deliveryPrice);
    }

    public function testOwnDeliveryIsFreeOverFiveThousand(): void
    {
        $place = self::place(delivery: true, coolBalik: false);

        self::assertSame(1000 + 35 + 195, AdminOrderPricing::orderPrice([self::item(1000, 1)], $place)->total());
        self::assertSame(5001 + 35, AdminOrderPricing::orderPrice([self::item(5001, 1)], $place)->total());
    }

    public function testCoolBalikDeliveryIsNeverFree(): void
    {
        self::assertSame(6000 + 35 + 195, AdminOrderPricing::orderPrice([self::item(6000, 1)], self::place(delivery: true, coolBalik: true))->total());
    }

    public function testFreeDeliveryThresholdUsesPriceBeforeDiscount(): void
    {
        // 4 600 after -10 % = 5 111 before → free own delivery, as on the web
        $item = new PricedOrderItem(4600, 5111, 1, false, 0);
        $price = AdminOrderPricing::orderPrice([$item], self::place(delivery: true, coolBalik: false));

        self::assertSame(4600 + 35, $price->total());
        self::assertSame(5111 + 35, $price->totalBeforeDiscount());
    }

    public function testStoredTotalIsScaledForUnchangedItems(): void
    {
        // Legacy turkey stored per kg: 127 Kč/kg, total 572 for one piece
        $item = new PricedOrderItem(127, 127, 2, false, 0, storedTotalPerAmount: 572.0);

        self::assertSame(1144, $item->priceTotal());
    }

    private static function product(null|int $price, null|int $type = 1): AdminOrderProduct
    {
        return new AdminOrderProduct(1, 'Produkt', 'kg', $price, null, $type, null, false, false, true);
    }

    private static function item(int $price, float $amount, bool $packed = false, int $packing = 0): PricedOrderItem
    {
        return new PricedOrderItem($price, $price, $amount, $packed, $packing);
    }

    private static function place(bool $delivery, bool $coolBalik): AdminOrderPlace
    {
        return new AdminOrderPlace(1, 'Místo', $coolBalik, $delivery);
    }
}
