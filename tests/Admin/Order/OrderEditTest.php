<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use CeskaKruta\Web\Tests\Support\HttpMock;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mime\Email;

/**
 * Admin order editing (`EditOrder`). Fixture orders: 5901 shop (prsa 2 kg × 289 + šunka 3 ks × 89 = 845),
 * 5902 own delivery (prsa 4 kg + half turkey stored per kg: 127 Kč/kg × 4–5 kg = 572), 5906 CoolBalík in Pohoda and Boxxi.
 */
final class OrderEditTest extends OrderAdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testEditPageIsPrefilled(): void
    {
        $crawler = $this->client->request('GET', '/admin/objednavky/' . self::SHOP_ORDER . '/upravit');

        self::assertResponseIsSuccessful();
        self::assertSame('Jana Nováková', $crawler->filter('#order_edit_name')->attr('value'));
        self::assertCount(2, $crawler->filter('[data-admin--order-items-target="row"]'));
        self::assertSame('289', $crawler->filter('#order_edit_items_0_price_per_unit')->attr('value'));
        // Prototype for "Přidat položku"
        self::assertStringContainsString('order_edit[items][__name__][product_id]', (string) $crawler->filter('[data-controller="admin--order-items"]')->attr('data-admin--order-items-prototype-value'));
    }

    public function testDetailAndListLinkToEdit(): void
    {
        $crawler = $this->client->request('GET', '/admin/objednavky/' . self::SHOP_ORDER);
        self::assertCount(1, $crawler->filter('a[href="/admin/objednavky/' . self::SHOP_ORDER . '/upravit"]'));

        $crawler = $this->requestList();
        self::assertCount(1, $this->row($crawler, self::SHOP_ORDER)->filter('a[href="/admin/objednavky/' . self::SHOP_ORDER . '/upravit"]'));
    }

    public function testChangesAmountRemovesAndAddsItems(): void
    {
        $values = $this->formValues(self::SHOP_ORDER);
        $values['order_edit']['items'][0]['amount'] = '3';
        unset($values['order_edit']['items'][1]);
        // New whole krůta, weight class given → 123 Kč/kg × 8 kg = 984 Kč/ks
        $values['order_edit']['items'][7] = ['product_id' => '1', 'amount' => '1', 'weight_from' => '7', 'weight_to' => '9', 'note' => 'větší kus'];

        $this->submit(self::SHOP_ORDER, $values);

        self::assertResponseRedirects('/admin/objednavky/' . self::SHOP_ORDER);
        self::assertSame([], $this->requests(), 'Shop order, nothing to send to Boxxi.');

        $items = $this->activeItems(self::SHOP_ORDER);
        self::assertSame([[100.0, 3.0, 289.0, 867.0], [1.0, 1.0, 984.0, 984.0]], array_map(fn (array $item): array => [
            $this->numeric($item, 'product_id'), $this->numeric($item, 'amount'), $this->numeric($item, 'price_per_unit'), $this->numeric($item, 'price_total'),
        ], $items));
        self::assertSame(1.0, $this->numeric($items[1], 'product_tp_id'));
        self::assertSame('větší kus', $items[1]['note']);
        self::assertSame(1.0, $this->numeric($this->fetchRow('order_item', 5902), 'del_flag'), 'Removed item is soft-deleted.');
        self::assertEqualsWithDelta(1851.0, $this->numeric($this->fetchRow('order', self::SHOP_ORDER), 'price_total'), 0.001);

        $this->client->followRedirect();
        $this->assertFlash('Objednávka uložena');
    }

    public function testDeliveryOrderGetsWebFeesAndKeepsLegacyTurkeyTotal(): void
    {
        $this->submit(self::DELIVERY_ORDER, $this->formValues(self::DELIVERY_ORDER));

        self::assertResponseRedirects('/admin/objednavky/' . self::DELIVERY_ORDER);
        // Items 1156 + 572 (stored per kg, kept) + 35 balné + 195 doprava (own delivery under 5 000)
        self::assertSame([1156.0, 572.0], array_map(fn (array $item): float => $this->numeric($item, 'price_total'), $this->activeItems(self::DELIVERY_ORDER)));
        self::assertEqualsWithDelta(1958.0, $this->numeric($this->fetchRow('order', self::DELIVERY_ORDER), 'price_total'), 0.001);
    }

    public function testNewTurkeyWithoutCalendarWeightIsRejected(): void
    {
        $values = $this->formValues(self::SHOP_ORDER);
        $values['order_edit']['items'][5] = ['product_id' => '1', 'amount' => '1'];

        $crawler = $this->submit(self::SHOP_ORDER, $values);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('není v kalendáři chlazených krůt váha', $crawler->filter('form')->text());
        self::assertCount(2, $this->activeItems(self::SHOP_ORDER), 'Nothing saved.');
    }

    public function testDeliveryPlaceNeedsAddress(): void
    {
        $values = $this->formValues(self::SHOP_ORDER);
        $values['order_edit']['place_id'] = '7';

        $crawler = $this->submit(self::SHOP_ORDER, $values);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Pro rozvoz je adresa povinná.', $crawler->filter('form')->text());
        self::assertSame(1.0, $this->numeric($this->fetchRow('order', self::SHOP_ORDER), 'place_id'));
    }

    public function testCoolBalikOrderWithShipmentContinuesToBoxxiForm(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);
        $values = $this->formValues(self::SYNCED_ORDER);
        $values['order_edit']['phone'] = '+420777000111';
        $values['order_edit']['note'] = 'Nová poznámka';

        $this->submit(self::SYNCED_ORDER, $values);

        self::assertResponseRedirects('/admin/objednavky/' . self::SYNCED_ORDER . '/boxxi');
        self::assertSame([], $this->requests(), 'The shipment is updated only after confirming the Boxxi form.');
        self::assertSame('+420777000111', $this->fetchRow('order', self::SYNCED_ORDER)['phone']);

        $crawler = $this->client->followRedirect();
        $flashes = $crawler->filter('.admin-flashes')->text();
        self::assertStringContainsString('aktualizujte zásilku v Boxxi', $flashes);
        self::assertStringContainsString('Poznámku Boxxi změnit neumí', $flashes);
        self::assertStringContainsString('Objednávka už je v Pohodě', $flashes);
    }

    public function testMovingAwayFromCoolBalikCancelsShipment(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);
        HttpMock::queue(new MockResponse('{"Status": "Cancelled", "ShipmentNumber": "BX5906"}', ['http_code' => 200]));
        $values = $this->formValues(self::SYNCED_ORDER);
        $values['order_edit']['place_id'] = '1';

        $this->submit(self::SYNCED_ORDER, $values);

        self::assertResponseRedirects('/admin/objednavky/' . self::SYNCED_ORDER);
        $requests = $this->requests();
        self::assertCount(1, $requests);
        self::assertSame(self::BOXXI_URL . '/BX5906/cancel', $requests[0]['url']);

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        self::assertSame(1.0, $this->numeric($order, 'place_id'));
        self::assertNull($order['boxxi_sync_dt']);
        self::assertNull($order['boxxi_shipment_number']);

        $this->client->followRedirect();
        $this->assertFlash('Zásilka v Boxxi byla zrušena, objednávka už není rozvoz CoolBalík.', 'info');
    }

    public function testFailedCancelRollsBackTheEdit(): void
    {
        $this->connection()->update('`order`', ['boxxi_shipment_number' => 'BX5906'], ['id' => self::SYNCED_ORDER]);
        HttpMock::queue(new MockResponse('{"Message": "Shipment already picked up"}', ['http_code' => 400]));
        $values = $this->formValues(self::SYNCED_ORDER);
        $values['order_edit']['place_id'] = '1';
        $values['order_edit']['name'] = 'Někdo jiný';

        $crawler = $this->submit(self::SYNCED_ORDER, $values);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Zásilku v Boxxi se nepodařilo zrušit', $crawler->filter('.admin-flashes')->text());

        $order = $this->fetchRow('order', self::SYNCED_ORDER);
        self::assertSame(21.0, $this->numeric($order, 'place_id'));
        self::assertSame('Karel Synchronizovaný', $order['name']);
        self::assertSame('BX5906', $order['boxxi_shipment_number']);
    }

    public function testNotifiesCustomerWithChanges(): void
    {
        $values = $this->formValues(self::SHOP_ORDER);
        $values['order_edit']['items'][0]['amount'] = '3';
        unset($values['order_edit']['items'][1]);
        $values['order_edit']['notify_customer'] = '1';

        $this->submit(self::SHOP_ORDER, $values);

        self::assertResponseRedirects('/admin/objednavky/' . self::SHOP_ORDER);
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('Změna objednávky č. ' . self::SHOP_ORDER, $email->getSubject());
        self::assertSame('jana.novakova@example.test', $email->getTo()[0]->getAddress());

        $html = (string) $email->getHtmlBody();
        self::assertStringContainsString('Co se změnilo', $html);
        self::assertStringContainsString('2 kg = 578 Kč', $html);
        self::assertStringContainsString('3 kg = 867 Kč', $html);
        self::assertStringContainsString('odebráno', $html);
        self::assertStringContainsString('845 Kč', $html);

        $crawler = $this->client->followRedirect();
        self::assertStringContainsString('Zákazníkovi byl odeslán e-mail se změnami.', $crawler->filter('.admin-flashes')->text());
    }

    public function testNoEmailByDefault(): void
    {
        $this->submit(self::SHOP_ORDER, $this->formValues(self::SHOP_ORDER));

        self::assertResponseRedirects();
        self::assertEmailCount(0);
    }

    /**
     * @return array{order_edit: array{place_id: string, name: string, phone: string, note: string, notify_customer?: string, items: array<int, array<string, string>>}}
     */
    private function formValues(int $orderId): array
    {
        $crawler = $this->client->request('GET', '/admin/objednavky/' . $orderId . '/upravit');
        self::assertResponseIsSuccessful();

        /** @var array{order_edit: array{place_id: string, name: string, phone: string, note: string, notify_customer?: string, items: array<int, array<string, string>>}} $values */
        $values = $crawler->selectButton('Uložit objednávku')->form()->getPhpValues();

        return $values;
    }

    /**
     * @param array{order_edit: array{place_id: string, name: string, phone: string, note: string, notify_customer?: string, items: array<int, array<string, string>>}} $values
     */
    private function submit(int $orderId, array $values): \Symfony\Component\DomCrawler\Crawler
    {
        return $this->client->request('POST', '/admin/objednavky/' . $orderId . '/upravit', $values);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeItems(int $orderId): array
    {
        return $this->connection()->fetchAllAssociative('SELECT * FROM order_item WHERE order_id = ? AND del_flag = 0 ORDER BY id', [$orderId]);
    }
}
