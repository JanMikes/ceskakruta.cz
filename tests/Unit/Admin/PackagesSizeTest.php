<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Admin;

use CeskaKruta\Web\Exceptions\InvalidPackagesSize;
use CeskaKruta\Web\Value\Admin\PackagesSize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * PLAN §2.9: `packages_size` input "500, 1000, 1500" (positive ints, deduplicated, sorted ascending),
 * stored as JSON int array `[500,1000,1500]`, empty → `[]`; the frontend (`GetProducts`) json_decodes it to array<int>.
 */
final class PackagesSizeTest extends TestCase
{
    /**
     * @param list<int> $expected
     */
    #[DataProvider('provideValidInput')]
    public function testParsesInput(string $input, array $expected): void
    {
        self::assertSame($expected, PackagesSize::fromInput($input)->sizes);
    }

    /**
     * @return iterable<string, array{string, list<int>}>
     */
    public static function provideValidInput(): iterable
    {
        yield 'deduplicated and sorted' => ['1000, 500, 500', [500, 1000]];
        yield 'single value' => ['500', [500]];
        yield 'without spaces' => ['500,1000,1500', [500, 1000, 1500]];
        yield 'surrounding whitespace' => ['  1500 ,500 , 1000  ', [500, 1000, 1500]];
        yield 'empty' => ['', []];
        yield 'whitespace only' => ['   ', []];
    }

    #[DataProvider('provideInvalidInput')]
    public function testRejectsInvalidInput(string $input): void
    {
        $this->expectException(InvalidPackagesSize::class);

        PackagesSize::fromInput($input);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidInput(): iterable
    {
        yield 'text' => ['abc'];
        yield 'text among numbers' => ['500, abc'];
        yield 'zero' => ['0'];
        yield 'negative' => ['500, -100'];
        yield 'decimal' => ['500, 1.5'];
        yield 'exponent' => ['1e3'];
    }

    public function testToJsonIsCompactIntArray(): void
    {
        self::assertSame('[500,1000,1500]', PackagesSize::fromInput('1500, 1000, 500')->toJson());
    }

    public function testEmptyToJsonIsEmptyArray(): void
    {
        self::assertSame('[]', PackagesSize::fromInput('')->toJson());
    }

    public function testJsonIsDecodedByFrontendAsIntArray(): void
    {
        // Same decoding as src/Query/GetProducts.php
        $decoded = json_decode(PackagesSize::fromInput('1000, 500')->toJson());

        self::assertSame([500, 1000], $decoded);
    }

    public function testFromJsonReadsStoredFormats(): void
    {
        self::assertSame([500, 1000], PackagesSize::fromJson('[500,1000]')->sizes);
        self::assertSame([500, 1000], PackagesSize::fromJson('[500, 1000]')->sizes);
        self::assertSame([], PackagesSize::fromJson('[]')->sizes);
    }

    public function testToInput(): void
    {
        self::assertSame('500, 1000, 1500', PackagesSize::fromJson('[500,1000,1500]')->toInput());
        self::assertSame('', PackagesSize::fromJson('[]')->toInput());
    }

    public function testJsonRoundTrip(): void
    {
        $original = PackagesSize::fromInput('250, 1000, 500');

        self::assertSame($original->sizes, PackagesSize::fromJson($original->toJson())->sizes);
        self::assertSame('[250,500,1000]', PackagesSize::fromJson('[250, 500, 1000]')->toJson());
    }

    public function testInputRoundTrip(): void
    {
        $original = PackagesSize::fromJson('[500,1000]');

        self::assertSame('[500,1000]', PackagesSize::fromInput($original->toInput())->toJson());
    }
}
