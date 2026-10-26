<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Pohoda;

use CeskaKruta\Web\Services\Pohoda\PohodaOrderXmlFactory;
use CeskaKruta\Web\Tests\Support\RestoresExceptionHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Characterization test: golden files tests/fixtures/pohoda/*.xml were rendered by the OLD app code
 * (`Pohoda::sendOrderRequest` + `Pohoda/*.xml.latte`, see tests/fixtures/pohoda/_renderer/) from the same data as GoldenOrders.
 * The factory is taken from the container (the implementation may use Twig), no database is touched.
 */
final class PohodaOrderXmlTest extends KernelTestCase
{
    use RestoresExceptionHandler;

    /**
     * @param array<string, string> $expectedFiles order number => golden file
     */
    #[DataProvider('provideOrders')]
    public function testMatchesGoldenFile(int $orderId, null|string $addressId, array $expectedFiles): void
    {
        $documents = $this->factory()->orderDocuments(GoldenOrders::order($orderId), GoldenOrders::items($orderId), $addressId);

        self::assertSame(array_keys($expectedFiles), array_keys($documents), 'Order numbers / sending order differ.');

        foreach ($expectedFiles as $orderNumber => $file) {
            XmlAssert::assertSameXml(GoldenOrders::file('pohoda/' . $file), $documents[$orderNumber], $orderNumber);
        }
    }

    /**
     * @return \Generator<string, array{int, null|string, array<string, string>}>
     */
    public static function provideOrders(): \Generator
    {
        yield 'shop order (place 1, cash, no address id)' => [5901, null, ['WEB5901' => 'order-WEB5901.xml']];
        yield 'delivery order with found address id, half turkey not split' => [5902, '1234', ['WEB5902' => 'order-WEB5902.xml']];
        yield 'delivery order with created address id' => [5902, '7777', ['WEB5902' => 'order-WEB5902-address-7777.xml']];
        yield 'CoolBalík split on Thursday expedition, card payment' => [5903, '1234', ['WEB5903' => 'order-WEB5903.xml', 'WEB5903K' => 'order-WEB5903K.xml']];
        yield 'coupon note' => [5904, null, ['WEB5904' => 'order-WEB5904.xml']];
        yield 'CoolBalík Monday expedition (no split), card payment, escaped note' => [5905, '1234', ['WEB5905' => 'order-WEB5905.xml']];
    }

    public function testSplitOrderWithOnlyWholeTurkeysSendsOnlyTurkeyDocument(): void
    {
        $items = array_values(array_filter(GoldenOrders::items(5903), static fn ($item): bool => $item->weightFrom !== null));

        $documents = $this->factory()->orderDocuments(GoldenOrders::order(5903), $items, '1234');

        self::assertSame(['WEB5903K'], array_keys($documents));
        self::assertSame('2024-06-18', XmlAssert::text($documents['WEB5903K'], '//ord:dateFrom'));
        self::assertSame('600001', XmlAssert::text($documents['WEB5903K'], '//ord:orderItem/ord:code'));
    }

    public function testSplitOrderWithoutWholeTurkeysSendsOnlyMainDocument(): void
    {
        $items = array_values(array_filter(GoldenOrders::items(5903), static fn ($item): bool => $item->weightFrom === null));

        $documents = $this->factory()->orderDocuments(GoldenOrders::order(5903), $items, '1234');

        self::assertSame(['WEB5903'], array_keys($documents));
        XmlAssert::assertSameXml(GoldenOrders::file('pohoda/order-WEB5903.xml'), $documents['WEB5903']);
    }

    #[DataProvider('provideSearchAddress')]
    public function testSearchAddressMatchesGoldenFile(int $orderId): void
    {
        XmlAssert::assertSameXml(
            GoldenOrders::file(sprintf('pohoda/search-address-%d.xml', $orderId)),
            $this->factory()->searchAddressXml(GoldenOrders::order($orderId)),
        );
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function provideSearchAddress(): \Generator
    {
        yield 'delivery' => [5902];
        yield 'CoolBalík' => [5903];
        yield 'CoolBalík 2' => [5905];
    }

    public function testCreateAddressMatchesGoldenFile(): void
    {
        XmlAssert::assertSameXml(
            GoldenOrders::file('pohoda/create-address-5902.xml'),
            $this->factory()->createAddressXml(GoldenOrders::order(5902)),
        );
    }

    private function factory(): PohodaOrderXmlFactory
    {
        return self::getContainer()->get(PohodaOrderXmlFactory::class);
    }
}
