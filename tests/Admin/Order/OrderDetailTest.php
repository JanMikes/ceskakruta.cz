<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Order detail `/admin/objednavky/{id}` (old `Order.detail.latte`). Labels and values are matched on the normalized
 * page text ("Label: value" or "Label value"), so th/td or dt/dd markup both work; items are table rows (`tr`).
 */
final class OrderDetailTest extends OrderAdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testShowsAllFieldsAndItemsSorted(): void
    {
        $crawler = $this->client->request('GET', '/admin/objednavky/5903');
        self::assertResponseIsSuccessful();
        $text = $this->pageText($crawler);

        self::assertMatchesRegularExpression('/Č\.:?\s*5903\s*Datum provedení/u', $text);
        self::assertStringNotContainsString('Nový web', $text);
        self::assertMatchesRegularExpression('/Datum provedení:?\s*14\.06\.2024 07:05/u', $text);
        foreach (['Marie Dvořáková', '+420600500600', 'marie.dvorakova@example.test', 'Školní 7', 'Olomouc 77900'] as $customer) {
            self::assertStringContainsString($customer, $text);
        }
        self::assertMatchesRegularExpression('/Sleva:?\s*-\s/u', $text);
        self::assertMatchesRegularExpression('/Cena celkem:?\s*1[ .\x{a0}]?467 Kč/u', $text);
        self::assertMatchesRegularExpression('/Platba kartou:?\s*Ano/u', $text);
        self::assertMatchesRegularExpression('/Místo:?\s*Rozvoz CoolBalík/u', $text);
        self::assertMatchesRegularExpression('/Datum:?\s*21\.06\.2024/u', $text);
        self::assertMatchesRegularExpression('/Poznámka:?\s*Doručit po 16\. hodině/u', $text);

        // Items sorted product_tp_id DESC, product_id ASC (fixture inserted them in the opposite order)
        $turkey = $this->itemRow($crawler, 'Krůta celá s droby');
        $meat = $this->itemRow($crawler, 'Krůtí prsa');
        $ham = $this->itemRow($crawler, 'Krůtí šunka');
        self::assertSame(['Krůta celá s droby', 'Krůtí prsa', 'Krůtí šunka'], $this->itemNamesInOrder($crawler, ['Krůta celá s droby', 'Krůtí prsa', 'Krůtí šunka']));

        // Položka, Cena j. ("{price} Kč / kg|unit"), Počet ("{amount} {unit}"), Váha ("from - to kg" or "-"), Cena celkem
        self::assertStringContainsString('123 Kč / kg', $turkey);
        self::assertStringContainsString('1 ks', $turkey);
        self::assertStringContainsString('6 - 7 kg', $turkey);
        self::assertStringContainsString('800 Kč', $turkey);

        self::assertStringContainsString('289 Kč / kg', $meat);
        self::assertStringContainsString('2 kg', $meat);
        self::assertStringContainsString('578 Kč', $meat);
        self::assertStringNotContainsString(' - ', str_replace('Kč / kg', '', $meat), 'No weight range for non-turkey items.');

        self::assertStringContainsString('89 Kč / ks', $ham);
        self::assertStringContainsString('1 ks', $ham);
        self::assertStringContainsString('89 Kč', $ham);
    }

    public function testShowsCouponAndCashPayment(): void
    {
        $crawler = $this->client->request('GET', '/admin/objednavky/5904');
        self::assertResponseIsSuccessful();
        $text = $this->pageText($crawler);

        self::assertMatchesRegularExpression('/Sleva:?\s*LETO10\s*-10%/u', $text);
        self::assertMatchesRegularExpression('/Cena celkem:?\s*520 Kč/u', $text);
        self::assertMatchesRegularExpression('/Platba kartou:?\s*Ne/u', $text);
        self::assertMatchesRegularExpression('/Místo:?\s*Rychvald - Prodejna/u', $text);
        self::assertStringNotContainsString('Velkoodběratel', $text, 'No badge for an order to a retail place.');
    }

    public function testWholesaleBadge(): void
    {
        // Place 22 = wholesale-only delivery place
        $this->connection()->executeStatement('UPDATE `order` SET place_id = 22 WHERE id = 5904');

        $crawler = $this->client->request('GET', '/admin/objednavky/5904');
        self::assertResponseIsSuccessful();
        self::assertMatchesRegularExpression('/Č\.:?\s*5904\s*Velkoodběratel/u', $this->pageText($crawler));
    }

    public function testUnknownIdRedirects(): void
    {
        $this->client->request('GET', '/admin/objednavky/999999');

        $this->assertRedirectsTo(self::LIST_URL);
    }

    private function itemRow(Crawler $crawler, string $productName): string
    {
        $rows = $crawler->filter('tr')->reduce(static fn (Crawler $row): bool => str_contains($row->text(), $productName));
        self::assertCount(1, $rows, sprintf('Item row "%s" not found.', $productName));

        return $rows->text();
    }

    /**
     * @param list<string> $names
     * @return list<string>
     */
    private function itemNamesInOrder(Crawler $crawler, array $names): array
    {
        $ordered = [];

        foreach ($crawler->filter('tr')->each(static fn (Crawler $row): string => $row->text()) as $rowText) {
            foreach ($names as $name) {
                if (str_contains($rowText, $name)) {
                    $ordered[] = $name;
                }
            }
        }

        return $ordered;
    }
}
