<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use CeskaKruta\Web\Tests\Support\HttpMock;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * `CancelBoxxiShipment`: "Zrušit zásilku v Boxxi" on the Boxxi page, only for orders with a stored `boxxi_shipment_number`.
 * Request: POST https://api.boxxi-logistics.cz/v1/shipments/{shipmentNumber}/cancel, body {"Reason": "…"}.
 */
final class CancelBoxxiShipmentTest extends OrderAdminTestCase
{
    private const CANCEL_BUTTON = 'Zrušit zásilku v Boxxi';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testCancelsShipmentAndAllowsResend(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);
        HttpMock::queue(new MockResponse('{"Status": "Cancelled", "ShipmentNumber": "BX5906"}', ['http_code' => 200]));

        $this->client->submit($this->buttonForm($this->boxxiPage(self::SYNCED_ORDER), self::CANCEL_BUTTON));

        $requests = $this->requests();
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame(self::BOXXI_URL . '/BX5906/cancel', $requests[0]['url']);
        self::assertSame(self::BOXXI_TOKEN, $this->header($requests[0], 'x-api-key'));
        self::assertSame(['Reason' => 'Zrušeno v administraci ceskakruta.cz'], json_decode($requests[0]['body'], true, flags: JSON_THROW_ON_ERROR));

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        self::assertNull($order['boxxi_sync_dt']);
        self::assertNull($order['boxxi_shipment_number']);
        // Form values are kept for the resend
        self::assertEqualsWithDelta(1500.0, $this->numeric($order, 'cool_balik_price'), 0.0001);

        $this->assertRedirectsTo('/admin/objednavky/5906/boxxi');
        $crawler = $this->client->followRedirect();
        $this->assertFlash('Zásilka v Boxxi zrušena');
        self::assertCount(1, $crawler->selectButton('Uložit a propsat'));
        self::assertCount(0, $crawler->selectButton(self::CANCEL_BUTTON));
    }

    public function testFailedCancelKeepsOrderSent(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);
        HttpMock::queue(new MockResponse('{"Message": "Shipment already delivered"}', ['http_code' => 400]));

        $this->client->submit($this->buttonForm($this->boxxiPage(self::SYNCED_ORDER), self::CANCEL_BUTTON));

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        self::assertSame('2024-06-20 10:30:00', $order['boxxi_sync_dt']);
        self::assertSame('BX5906', $order['boxxi_shipment_number']);

        $this->assertRedirectsTo('/admin/objednavky/5906/boxxi');
        $this->client->followRedirect();
        $this->assertFlash('Zásilku se nepodařilo v Boxxi zrušit', 'danger');
    }

    public function testNoCancelForOrderWithoutShipmentNumber(): void
    {
        // 5906 was sent before shipment numbers were stored
        $crawler = $this->boxxiPage(self::SYNCED_ORDER);
        self::assertCount(0, $crawler->selectButton(self::CANCEL_BUTTON));
        self::assertStringContainsString('Číslo zásilky není uloženo', $this->pageText($crawler));

        $this->client->request('POST', '/admin/objednavky/5906/boxxi/zrusit', ['_token' => (string) $this->requestList()->filter('input[name="_token"]')->first()->attr('value')]);

        self::assertSame([], $this->requests());
        self::assertSame('2024-06-20 10:30:00', $this->fetchRow('order', self::SYNCED_ORDER)['boxxi_sync_dt']);
        $this->client->followRedirect();
        $this->assertFlash('Číslo zásilky v Boxxi není uloženo', 'danger');
    }

    public function testRejectsInvalidCsrfToken(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);

        $this->client->request('POST', '/admin/objednavky/5906/boxxi/zrusit', ['_token' => 'invalid']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame([], $this->requests());
        self::assertSame('BX5906', $this->fetchRow('order', self::SYNCED_ORDER)['boxxi_shipment_number']);
    }

    private function boxxiPage(int $orderId): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $this->client->request('GET', sprintf('/admin/objednavky/%d/boxxi', $orderId));
        self::assertResponseIsSuccessful();

        return $crawler;
    }
}
