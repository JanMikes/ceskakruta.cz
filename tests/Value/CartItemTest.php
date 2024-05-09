<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Value;

use Generator;
use CeskaKruta\Web\Value\CartItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CartItemTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testIsSame(
        int $productIdA,
        int $productIdB,
        bool $expected,
    ): void
    {
        $a = new CartItem($productIdA);
        $b = new CartItem($productIdB);

        $this->assertSame($expected, $a->isSame($b));
        $this->assertSame($expected, $b->isSame($a));
    }

    /**
     * @return Generator<array{int, int, bool}>
     */
    public static function provideValues(): Generator
    {
        $id1 = 1;
        $id2 = 2;

        yield [$id1, $id1, true];

        yield [$id1, $id2, false];
    }


    public function testToArray(): void
    {
        $id = 1;
        $item = new CartItem($id);

        $expected = [
            'product_id' => $id,
        ];

        $this->assertSame($expected, $item->toArray());
    }

    public function testFromArrayWithout(): void
    {
        $id = 1;

        $data = [
            'product_id' => $id,
            'dimensions' => null,
        ];

        $item = CartItem::fromArray($data);

        $this->assertSame($id, $item->productId);
    }
}
