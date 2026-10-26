<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Pohoda;

use CeskaKruta\Web\Services\Pohoda\PohodaItemCode;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Old `OrderService::getOrderItemCode()` / `getOrderItemName()` (libs/services/OrderService.php:597–703).
 */
final class PohodaItemCodeTest extends TestCase
{
    #[DataProvider('provideTurkeys')]
    public function testTurkeyMapping(int $productTypeId, int $productId, string $expectedCode, string $expectedName): void
    {
        $item = self::item($productId, $productTypeId, productCode: 'IGNORED', productName: 'Product name from DB');
        $itemCode = new PohodaItemCode();

        self::assertSame($expectedCode, $itemCode->code($item));
        self::assertSame($expectedName, $itemCode->name($item));
    }

    /**
     * @return \Generator<string, array{int, int, string, string}>
     */
    public static function provideTurkeys(): \Generator
    {
        yield 'Krůta celá s droby' => [1, 1, '600001', 'Krůta celá s droby'];
        yield 'Krůta půlka bez drobů' => [1, 2, '600002', 'Krůta - půlka bez drobů'];
        yield 'Porcovaná krůta celá' => [1, 41, '600040', 'Porcovaná Krůta - celý kus'];
        yield 'Porcovaná krůta půlka' => [1, 42, '600041', 'Porcovaná Krůta - půlka'];
        yield 'Krocan celý s droby' => [2, 1, '600045', 'Krocan celý s droby'];
        yield 'Krocan půlka bez drobů' => [2, 2, '600046', 'Krocan - půlka bez drobů'];
        yield 'Porcovaný krocan celý' => [2, 41, '600047', 'Porcovaný Krocan - celý kus'];
        yield 'Porcovaný krocan půlka' => [2, 42, '600048', 'Porcovaný Krocan - půlka'];
    }

    #[DataProvider('provideFallbacks')]
    public function testFallsBackToProductCodeAndName(AdminOrderItem $item, null|string $expectedCode, string $expectedName): void
    {
        $itemCode = new PohodaItemCode();

        self::assertSame($expectedCode, $itemCode->code($item));
        self::assertSame($expectedName, $itemCode->name($item));
    }

    /**
     * @return \Generator<string, array{AdminOrderItem, null|string, string}>
     */
    public static function provideFallbacks(): \Generator
    {
        yield 'regular product' => [self::item(100, null, '100100', 'Krůtí prsa'), '100100', 'Krůtí prsa'];
        yield 'product without code' => [self::item(102, null, null, 'Krůtí paštika'), null, 'Krůtí paštika'];
        // Mapping needs product_tp_id, without it the turkey product row itself is used
        yield 'turkey product id without type' => [self::item(1, null, null, 'Krůta celá s droby (DB)'), null, 'Krůta celá s droby (DB)'];
        // Tom products 47–50 have no mapping in the old code
        yield 'type 1 with unmapped product' => [self::item(47, 1, '470047', 'Krocan celý s droby (DB)'), '470047', 'Krocan celý s droby (DB)'];
        yield 'type 2 with unmapped product' => [self::item(100, 2, '100100', 'Krůtí prsa'), '100100', 'Krůtí prsa'];
        yield 'unknown type' => [self::item(1, 3, 'X1', 'Jiný typ'), 'X1', 'Jiný typ'];
    }

    private static function item(int $productId, null|int $productTypeId, null|string $productCode, string $productName): AdminOrderItem
    {
        return new AdminOrderItem(1, $productId, $productTypeId, $productName, $productCode, 'ks', 1.0, 1.0, 100, null, null);
    }
}
