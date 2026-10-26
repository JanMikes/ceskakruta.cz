<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Boxxi;

use CeskaKruta\Web\Services\Boxxi\ShipmentWeightCalculator;
use CeskaKruta\Web\Tests\Unit\Pohoda\GoldenOrders;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Old `CoolBalik::calculateWeight()` / `calculateBoxes()` (libs/services/CoolBalik.php:81–100).
 */
final class ShipmentWeightCalculatorTest extends TestCase
{
    /**
     * @param list<AdminOrderItem> $items
     */
    #[DataProvider('provideWeights')]
    public function testWeight(array $items, float $expected): void
    {
        self::assertEqualsWithDelta($expected, (new ShipmentWeightCalculator())->weight($items), 0.000001);
    }

    /**
     * @return \Generator<string, array{list<AdminOrderItem>, float}>
     */
    public static function provideWeights(): \Generator
    {
        yield 'no items' => [[], 0.0];
        yield 'turkey: average of weight_from and weight_to' => [[GoldenOrders::turkey(1, 1, 1, 'Krůta', 123, 8, 9)], 8.5];
        yield 'two turkeys: amount × average' => [[GoldenOrders::turkey(1, 1, 1, 'Krůta', 123, 6, 7, amount: 2)], 13.0];
        yield 'ks product: amount × weight_per_unit' => [[GoldenOrders::ham(1, 3)], 0.9];
        yield 'kg product: amount' => [[GoldenOrders::meat(1, 2.5)], 2.5];
        yield 'turkey without weight_to falls back to ks × weight_per_unit' => [
            [new AdminOrderItem(1, 1, 1, 'Krůta', null, 'ks', 1.0, 2.0, 123, 8.0, null)],
            2.0,
        ];
        yield 'order 5903: 1 × 6.5 + 2 kg + 1 × 0.3' => [GoldenOrders::items(5903), 8.8];
        yield 'order 5905: 1 × 11 + 2 × 0.3 + 10 kg' => [GoldenOrders::items(5905), 21.6];
    }

    #[DataProvider('provideBoxes')]
    public function testBoxes(float $weight, int $expected): void
    {
        self::assertSame($expected, (new ShipmentWeightCalculator())->boxes($weight));
    }

    /**
     * @return \Generator<string, array{float, int}>
     */
    public static function provideBoxes(): \Generator
    {
        yield '0 kg' => [0.0, 0];
        yield '8.8 kg' => [8.8, 1];
        yield 'exactly 20 kg' => [20.0, 1];
        yield '20.01 kg' => [20.01, 2];
        yield '21.6 kg' => [21.6, 2];
        yield 'exactly 40 kg' => [40.0, 2];
        yield '41 kg' => [41.0, 3];
    }
}
