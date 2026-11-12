<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Admin\Order;

use CeskaKruta\Web\Services\Admin\Order\OrderChangeList;
use CeskaKruta\Web\Value\Admin\AdminOrder;
use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use CeskaKruta\Web\Value\Admin\Order\OrderChange;
use PHPUnit\Framework\TestCase;

final class OrderChangeListTest extends TestCase
{
    public function testListsChangedFieldsAndItems(): void
    {
        $before = self::record('2024-06-14', 'Vyzvednu odpoledne', 845);
        $after = self::record('2024-06-15', 'Vyzvednu odpoledne', 1851);

        $changes = OrderChangeList::between(
            $before,
            [self::item(1, 100, 'Krůtí prsa', 2, 'kg', 578), self::item(2, 101, 'Krůtí šunka', 3, 'ks', 267)],
            $after,
            [self::item(1, 100, 'Krůtí prsa', 3, 'kg', 867, sliced: true), self::item(3, 1, 'Krůta celá s droby', 1, 'ks', 984, 7, 9)],
            true,
        );

        self::assertSame([
            ['Datum', '14.06.2024', '15.06.2024'],
            ['Krůtí prsa', '2 kg = 578 Kč', '3 kg, nakrájet = 867 Kč'],
            ['Krůta celá s droby', '–', '1 ks, 7–9 kg/ks = 984 Kč'],
            ['Krůtí šunka', '3 ks = 267 Kč', 'odebráno'],
            ['Cena celkem', '845 Kč', '1 851 Kč'],
        ], array_map(static fn (OrderChange $change): array => [$change->label, $change->before, $change->after], $changes));
    }

    public function testWithoutPricesAndWithoutChanges(): void
    {
        $record = self::record('2024-06-14', '', 845);
        $items = [self::item(1, 100, 'Krůtí prsa', 2.5, 'kg', 723)];

        self::assertSame([], OrderChangeList::between($record, $items, $record, $items, false));
        self::assertSame('2,5 kg', OrderChangeList::item($items[0], false));
    }

    private static function record(string $date, string $note, float $price): AdminOrderRecord
    {
        return new AdminOrderRecord(
            order: new AdminOrder(5901, new \DateTimeImmutable('2024-06-10'), new \DateTimeImmutable($date), 'Jana', 'jana@example.test', '+420600100200', null, null, null, $note, false, null, new AdminOrderPlace(1, 'Rychvald', false, false)),
            priceTotal: $price,
            isWholesale: false,
            couponCode: null,
            pohodaSyncedAt: null,
            boxxiSyncedAt: null,
            coolBalikPrice: null,
            boxxiShipmentNumber: null,
        );
    }

    private static function item(int $id, int $productId, string $name, float $amount, string $unit, int $total, null|float $weightFrom = null, null|float $weightTo = null, bool $sliced = false): AdminOrderDetailItem
    {
        return new AdminOrderDetailItem(
            new AdminOrderItem($id, $productId, null, $name, null, $unit, 1.0, $amount, 0, $weightFrom, $weightTo, isSliced: $sliced),
            $total,
        );
    }
}
