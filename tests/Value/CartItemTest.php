<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Value;

use Generator;
use CeskaKruta\Web\Value\CartItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CartItemTest extends TestCase
{
    #[DataProvider('provideValues')]
    public function testIsSame(
        UuidInterface $variantIdA,
        UuidInterface $variantIdB,
        bool $expected,
    ): void
    {
        $a = new CartItem($variantIdA);
        $b = new CartItem($variantIdB);

        $this->assertSame($expected, $a->isSame($b));
        $this->assertSame($expected, $b->isSame($a));
    }

    /**
     * @return Generator<array{UuidInterface, UuidInterface, bool}>
     */
    public static function provideValues(): Generator
    {
        $uuid1 = Uuid::uuid7();
        $uuid2 = Uuid::uuid7();

        yield [$uuid1, $uuid1, true];

        yield [$uuid1, $uuid2, false];
    }


    public function testToArray(): void
    {
        $uuid = Uuid::uuid7();
        $item = new CartItem($uuid);

        $expected = [
            'variant_id' => $uuid->toString(),
        ];

        $this->assertSame($expected, $item->toArray());
    }

    public function testFromArrayWithout(): void
    {
        $uuid = Uuid::uuid7();

        $data = [
            'variant_id' => $uuid->toString(),
            'dimensions' => null,
        ];

        $item = CartItem::fromArray($data);

        $this->assertTrue($uuid->equals($item->productVariantId));
    }
}
