<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Boxxi;

use CeskaKruta\Web\Services\Boxxi\BoxxiShipmentFactory;
use CeskaKruta\Web\Tests\Unit\Pohoda\GoldenOrders;
use CeskaKruta\Web\Value\Admin\BoxxiShipmentData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test: tests/fixtures/boxxi/*.json is the exact request body the OLD `Boxxi::sendOrderRequest()` built
 * (tests/fixtures/pohoda/_renderer/) for the order row re-fetched after the form update. Compared after JSON decoding
 * with assertSame → key order and value types (int CodAmount, string ClientOrderNumber, int PackageCount, null StreetLine*) count.
 */
final class BoxxiShipmentFactoryTest extends TestCase
{
    #[DataProvider('provideShipments')]
    public function testMatchesGoldenFile(int $orderId, BoxxiShipmentData $shipment, string $goldenFile): void
    {
        $payload = (new BoxxiShipmentFactory())->payload(GoldenOrders::order($orderId), $shipment);

        self::assertSame(self::decode(GoldenOrders::file('boxxi/' . $goldenFile)), self::decode(json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    /**
     * @return \Generator<string, array{int, BoxxiShipmentData, string}>
     */
    public static function provideShipments(): \Generator
    {
        yield 'changed delivery date, decimal weight' => [
            5903,
            new BoxxiShipmentData(new \DateTimeImmutable('2024-06-20'), 1700.0, 1, 12.5),
            'shipment-5903.json',
        ];
        yield 'integer weight, note with quotes' => [
            5905,
            new BoxxiShipmentData(new \DateTimeImmutable('2024-06-25'), 4487.0, 2, 22.0),
            'shipment-5905-integer-weight.json',
        ];
    }

    public function testCodAmountIsTruncatedToInt(): void
    {
        $payload = (new BoxxiShipmentFactory())->payload(
            GoldenOrders::order(5903),
            new BoxxiShipmentData(new \DateTimeImmutable('2024-06-20'), 1700.9, 1, 12.5),
        );

        // old: (int) $orderRow->cool_balik_price
        self::assertSame(1700, $payload['CodAmount']);
        self::assertSame('2024-06-20 12:00:00', $payload['DeliverAt']);
    }

    /**
     * @return array<mixed>
     */
    private static function decode(string $json): array
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
