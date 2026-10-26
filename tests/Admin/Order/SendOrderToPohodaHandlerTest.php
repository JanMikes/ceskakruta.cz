<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use CeskaKruta\Web\Tests\Support\HttpMock;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * `SendOrderToPohoda` (old `Pohoda::sendOrderRequest`), tested black-box through `POST /admin/objednavky/{id}/pohoda`
 * (PLAN §4.3: handler tests as functional tests). Every outgoing request is asserted via HttpMock: POST to POHODA_URL,
 * `STW-Authorization: POHODA_AUTHORIZATION`, `Content-Type: text/xml`, body = golden file rendered by the old app.
 * Pohoda responses: tests/fixtures/pohoda/responses/.
 */
final class SendOrderToPohodaHandlerTest extends OrderAdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testSearchesThenCreatesAddressForDelivery(): void
    {
        $this->queueResponses('search-not-found', 'address-created', 'order-ok');

        $this->sendToPohoda(self::DELIVERY_ORDER);

        $requests = $this->requests();
        self::assertCount(3, $requests);
        $this->assertPohodaRequest($requests[0], 'search-address-5902.xml');
        $this->assertPohodaRequest($requests[1], 'create-address-5902.xml');
        // id from the create response (rdc:producedDetails/rdc:id = 7777) is the partnerIdentity
        $this->assertPohodaRequest($requests[2], 'order-WEB5902-address-7777.xml');
        self::assertNotNull($this->pohodaSyncDate(self::DELIVERY_ORDER));
    }

    public function testUsesFoundAddress(): void
    {
        $this->queueResponses('search-found', 'order-ok');

        $this->sendToPohoda(self::DELIVERY_ORDER);

        $requests = $this->requests();
        self::assertCount(2, $requests, 'Found address (adb:id 1234) → no create request.');
        $this->assertPohodaRequest($requests[0], 'search-address-5902.xml');
        $this->assertPohodaRequest($requests[1], 'order-WEB5902.xml');
    }

    public function testShopOrderSkipsAddress(): void
    {
        $this->queueResponses('order-ok');

        $this->sendToPohoda(self::SHOP_ORDER);

        $requests = $this->requests();
        self::assertCount(1, $requests);
        $this->assertPohodaRequest($requests[0], 'order-WEB5901.xml');
    }

    public function testCouponOrder(): void
    {
        $this->queueResponses('order-ok');

        $this->sendToPohoda(self::COUPON_ORDER);

        $requests = $this->requests();
        self::assertCount(1, $requests);
        $this->assertPohodaRequest($requests[0], 'order-WEB5904.xml');
    }

    public function testSplitsTurkeysForCoolBalik(): void
    {
        $this->queueResponses('search-found', 'order-ok', 'order-ok');

        $this->sendToPohoda(self::COOL_BALIK_SPLIT_ORDER);

        $requests = $this->requests();
        self::assertCount(3, $requests);
        $this->assertPohodaRequest($requests[0], 'search-address-5903.xml');
        $this->assertPohodaRequest($requests[1], 'order-WEB5903.xml');
        $this->assertPohodaRequest($requests[2], 'order-WEB5903K.xml');
        self::assertNotNull($this->pohodaSyncDate(self::COOL_BALIK_SPLIT_ORDER));
    }

    public function testCoolBalikWithoutSplit(): void
    {
        $this->queueResponses('search-found', 'order-ok');

        $this->sendToPohoda(self::COOL_BALIK_ORDER);

        $requests = $this->requests();
        self::assertCount(2, $requests, 'Monday expedition → one order incl. the whole turkey.');
        $this->assertPohodaRequest($requests[0], 'search-address-5905.xml');
        $this->assertPohodaRequest($requests[1], 'order-WEB5905.xml');
    }

    public function testSetsSyncDate(): void
    {
        self::assertNull($this->pohodaSyncDate(self::SHOP_ORDER));
        $this->queueResponses('order-ok');
        $before = new \DateTimeImmutable('-1 minute');

        $this->sendToPohoda(self::SHOP_ORDER);

        $syncDate = $this->pohodaSyncDate(self::SHOP_ORDER);
        self::assertIsString($syncDate);
        self::assertGreaterThanOrEqual($before, new \DateTimeImmutable($syncDate));

        $crawler = $this->followRedirectToList();
        $this->assertFlash('Objednávka propsána do Pohody');
        self::assertCount(0, $crawler->filter('form[action="/admin/objednavky/5901/pohoda"]'), 'Synced order has no "Propsat" form any more.');
    }

    #[DataProvider('provideHttpErrors')]
    public function testHttpErrorKeepsOrderUnsynced(MockResponse $failure): void
    {
        HttpMock::queue($failure);

        $this->sendToPohoda(self::SHOP_ORDER);

        self::assertCount(1, $this->requests());
        self::assertNull($this->pohodaSyncDate(self::SHOP_ORDER));

        $this->followRedirectToList();
        self::assertSelectorExists('.alert-danger');
        self::assertSelectorNotExists('.alert-success');
    }

    /**
     * Old code only failed on curl errors (transport); an HTTP error status is treated as failure too (PLAN §4.4).
     *
     * @return \Generator<string, array{MockResponse}>
     */
    public static function provideHttpErrors(): \Generator
    {
        yield 'transport error' => [new MockResponse('', ['error' => 'Connection refused'])];
        yield 'HTTP 500' => [new MockResponse('Internal Server Error', ['http_code' => 500])];
    }

    public function testAddressSearchErrorSendsNoOrder(): void
    {
        HttpMock::queue(new MockResponse('', ['error' => 'Connection refused']));

        $this->sendToPohoda(self::DELIVERY_ORDER);

        self::assertCount(1, $this->requests(), 'No order is sent when the address lookup fails.');
        self::assertNull($this->pohodaSyncDate(self::DELIVERY_ORDER));
    }

    private function queueResponses(string ...$names): void
    {
        foreach ($names as $name) {
            HttpMock::queue($this->pohodaResponse($name));
        }
    }
}
