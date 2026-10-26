<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use CeskaKruta\Web\Tests\Support\HttpMock;
use CeskaKruta\Web\Tests\Unit\Pohoda\GoldenOrders;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * `SendOrderToBoxxi` (old `CoolBalikFormFactory::process` + `Boxxi::sendOrderRequest`), tested through the Boxxi form.
 * Request: POST https://api.boxxi-logistics.cz/v1/shipments via the `coolbalik.client` scoped client (`x-api-key: COOLBALIK_TOKEN`),
 * JSON body = golden file rendered by the old app (compared decoded, assertSame → key order + types).
 */
final class SendOrderToBoxxiHandlerTest extends OrderAdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testUpdatesOrderAndSendsPayload(): void
    {
        HttpMock::queue(new MockResponse('{"Id": 1}', ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/json']]));

        $this->submitBoxxi(self::COOL_BALIK_SPLIT_ORDER, '12.5', '1700', '1', '2024-06-20');

        $order = $this->fetchRow('order', self::COOL_BALIK_SPLIT_ORDER);
        self::assertSame('2024-06-20', $order['date']);
        self::assertEqualsWithDelta(1700.0, $this->numeric($order, 'cool_balik_price'), 0.0001);
        self::assertSame(1.0, $this->numeric($order, 'cool_balik_box_count'));
        self::assertEqualsWithDelta(12.5, $this->numeric($order, 'cool_balik_weight'), 0.0001);
        self::assertEqualsWithDelta(12.5, $this->numeric($order, 'boxxi_weight'), 0.0001);

        $requests = $this->requests();
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame(self::BOXXI_URL, $requests[0]['url']);
        self::assertSame(self::BOXXI_TOKEN, $this->header($requests[0], 'x-api-key'));
        self::assertStringContainsString('application/json', (string) $this->header($requests[0], 'Content-Type'));
        self::assertSame(
            json_decode(GoldenOrders::file('boxxi/shipment-5903.json'), true, flags: JSON_THROW_ON_ERROR),
            json_decode($requests[0]['body'], true, flags: JSON_THROW_ON_ERROR),
        );

        $this->followRedirectToList();
        $this->assertFlash('Objednávka propsána do Boxxi');
    }

    public function testSetsSyncDate(): void
    {
        HttpMock::queue(new MockResponse('{"Id": 1}', ['http_code' => 200]));
        self::assertNull($this->fetchRow('order', self::COOL_BALIK_ORDER)['boxxi_sync_dt']);
        $before = new \DateTimeImmutable('-1 minute');

        $this->submitBoxxi(self::COOL_BALIK_ORDER, '22', '4487', '2', '2024-06-25');

        $syncDate = $this->fetchRow('order', self::COOL_BALIK_ORDER)['boxxi_sync_dt'];
        self::assertIsString($syncDate);
        self::assertGreaterThanOrEqual($before, new \DateTimeImmutable($syncDate));
        self::assertCount(1, $this->requests());
        self::assertSame(
            json_decode(GoldenOrders::file('boxxi/shipment-5905-integer-weight.json'), true, flags: JSON_THROW_ON_ERROR),
            json_decode($this->requests()[0]['body'], true, flags: JSON_THROW_ON_ERROR),
        );

        // Synced → the list shows the sync datetime + final price instead of "Propsat"
        $crawler = $this->followRedirectToList();
        $row = $this->row($crawler, self::COOL_BALIK_ORDER);
        self::assertCount(0, $row->filter('a[href="/admin/objednavky/5905/boxxi"]'));
        self::assertStringContainsString('4487 Kč', $row->text());
    }

    public function testDoesNotResendWhenAlreadySynced(): void
    {
        // No response queued: any request would throw in HttpMock
        $this->submitBoxxi(self::SYNCED_ORDER, '6', '1600', '1', '2024-06-22');

        self::assertSame([], $this->requests());

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        // Form values are still saved (old behaviour), sync date kept
        self::assertEqualsWithDelta(1600.0, $this->numeric($order, 'cool_balik_price'), 0.0001);
        self::assertSame('2024-06-22', $order['date']);
        self::assertSame('2024-06-20 10:30:00', $order['boxxi_sync_dt']);

        $crawler = $this->followRedirectToList();
        self::assertStringNotContainsString('Objednávka propsána do Boxxi', $this->pageText($crawler));
    }

    private function submitBoxxi(int $orderId, string $weight, string $price, string $boxes, string $date): void
    {
        $crawler = $this->client->request('GET', sprintf('/admin/objednavky/%d/boxxi', $orderId));
        self::assertResponseIsSuccessful();

        $this->client->submit($this->buttonForm($crawler, 'Uložit a propsat'), [
            'boxxi[weight]' => $weight,
            'boxxi[cool_balik_price]' => $price,
            'boxxi[cool_balik_box_count]' => $boxes,
            'boxxi[date]' => $date,
        ]);
    }
}
