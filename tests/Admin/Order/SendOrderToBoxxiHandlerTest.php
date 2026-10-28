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
        HttpMock::queue(new MockResponse('{"Status": "ApproveShipmentProcess", "ShipmentNumber": "BX5903"}', ['http_code' => 200, 'response_headers' => ['Content-Type' => 'application/json']]));

        $this->submitBoxxi(self::COOL_BALIK_SPLIT_ORDER, '12.5', '1700', '1', '2024-06-20');

        $order = $this->fetchRow('order', self::COOL_BALIK_SPLIT_ORDER);
        self::assertSame('BX5903', $order['boxxi_shipment_number']);
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
        // Response without ShipmentNumber: still synced (the shipment exists in Boxxi), number unknown
        self::assertNull($this->fetchRow('order', self::COOL_BALIK_ORDER)['boxxi_shipment_number']);
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

    public function testCreateFailureKeepsOrderUnsynced(): void
    {
        HttpMock::queue(new MockResponse('{"Message": "Invalid postcode"}', ['http_code' => 400]));

        $this->submitBoxxi(self::COOL_BALIK_ORDER, '22', '4487', '2', '2024-06-30');

        self::assertResponseIsSuccessful();
        $this->assertFlash('Objednávku se nepodařilo propsat do Boxxi', 'danger');

        $order = $this->fetchRow('order', self::COOL_BALIK_ORDER);
        self::assertNull($order['boxxi_sync_dt']);
        self::assertNull($order['boxxi_shipment_number']);
        self::assertSame('2024-06-25', $order['date']);
    }

    public function testUpdatesShipmentWhenNumberKnown(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX 5906/A'], ['id' => self::SYNCED_ORDER]);
        HttpMock::queue(new MockResponse('{"Status": "ApproveShipmentProcess", "ShipmentNumber": "BX 5906/A"}', ['http_code' => 200]));

        $this->submitBoxxi(self::SYNCED_ORDER, '7.25', '1650', '2', '2024-06-23', 'Uložit a aktualizovat v Boxxi');

        $requests = $this->requests();
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['method']);
        // Shipment number is URL-encoded in the path
        self::assertSame(self::BOXXI_URL . '/BX%205906%2FA/update', $requests[0]['url']);
        self::assertSame(self::BOXXI_TOKEN, $this->header($requests[0], 'x-api-key'));
        self::assertSame(
            ['PackageCount' => 2, 'Weight' => 7.25, 'Cod' => 1650, 'DeliverAt' => '2024-06-23 12:00:00'],
            json_decode($requests[0]['body'], true, flags: JSON_THROW_ON_ERROR),
        );

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        self::assertEqualsWithDelta(1650.0, $this->numeric($order, 'cool_balik_price'), 0.0001);
        self::assertSame('2024-06-23', $order['date']);
        // Still the same shipment, sync date kept
        self::assertSame('2024-06-20 10:30:00', $order['boxxi_sync_dt']);
        self::assertSame('BX 5906/A', $order['boxxi_shipment_number']);

        $this->followRedirectToList();
        $this->assertFlash('Zásilka v Boxxi aktualizována');
    }

    public function testUpdateFailureRollsBackFormValues(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);
        HttpMock::queue(new MockResponse('{"Message": "Shipment already picked up"}', ['http_code' => 400]));

        $this->submitBoxxi(self::SYNCED_ORDER, '7', '1650', '2', '2024-06-23', 'Uložit a aktualizovat v Boxxi');

        self::assertResponseIsSuccessful();
        $this->assertFlash('Zásilku se nepodařilo v Boxxi aktualizovat', 'danger');

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        self::assertEqualsWithDelta(1500.0, $this->numeric($order, 'cool_balik_price'), 0.0001);
        self::assertSame('2024-06-21', $order['date']);
        self::assertSame('BX5906', $order['boxxi_shipment_number']);
    }

    private function submitBoxxi(int $orderId, string $weight, string $price, string $boxes, string $date, string $button = 'Uložit a propsat'): void
    {
        $crawler = $this->client->request('GET', sprintf('/admin/objednavky/%d/boxxi', $orderId));
        self::assertResponseIsSuccessful();

        $this->client->submit($this->buttonForm($crawler, $button), [
            'boxxi[weight]' => $weight,
            'boxxi[cool_balik_price]' => $price,
            'boxxi[cool_balik_box_count]' => $boxes,
            'boxxi[date]' => $date,
        ]);
    }
}
