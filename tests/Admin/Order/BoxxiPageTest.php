<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Form;

/**
 * Boxxi page `GET /admin/objednavky/{id}/boxxi` (old `Order.coolBalik.latte` + `CoolBalikFormFactory`),
 * form `boxxi[weight|cool_balik_price|cool_balik_box_count|date]`, button "Uložit a propsat".
 */
final class BoxxiPageTest extends OrderAdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    #[DataProvider('provideDefaults')]
    public function testDefaults(int $orderId, float $weight, float $price, int $boxes, string $date): void
    {
        $form = $this->boxxiForm($orderId);

        // weight = Σ amount × avg(weight_from, weight_to) | amount × weight_per_unit (ks) | amount (kg)
        self::assertEqualsWithDelta($weight, (float) $this->fieldValue($form, 'boxxi[weight]'), 0.0001);
        // price = order.price_total, boxes = ceil(weight / 20), date = order.date
        self::assertEqualsWithDelta($price, (float) $this->fieldValue($form, 'boxxi[cool_balik_price]'), 0.0001);
        self::assertSame((string) $boxes, $this->fieldValue($form, 'boxxi[cool_balik_box_count]'));
        self::assertSame($date, $this->fieldValue($form, 'boxxi[date]'));
    }

    /**
     * @return \Generator<string, array{int, float, float, int, string}>
     */
    public static function provideDefaults(): \Generator
    {
        yield '5903: 1 × avg(6, 7) + 2 kg + 1 × 0.3 kg/ks' => [5903, 8.8, 1467.0, 1, '2024-06-21'];
        yield '5905: 1 × avg(10, 12) + 2 × 0.3 kg/ks + 10 kg → 2 boxes' => [5905, 21.6, 4487.0, 2, '2024-06-25'];
    }

    public function testShowsOrderSummary(): void
    {
        $crawler = $this->client->request('GET', '/admin/objednavky/5903/boxxi');
        self::assertResponseIsSuccessful();
        $text = $this->pageText($crawler);

        foreach (['5903', 'Marie Dvořáková', '+420600500600', 'marie.dvorakova@example.test', 'Školní 7', 'Olomouc 77900',
            'Rozvoz CoolBalík', '21.06.2024', 'Doručit po 16. hodině', 'Krůta celá s droby', 'Krůtí prsa', 'Krůtí šunka'] as $expected) {
            self::assertStringContainsString($expected, $text);
        }
        self::assertMatchesRegularExpression('/Platba kartou:?\s*Ano/u', $text);
        self::assertMatchesRegularExpression('/1[ .\x{a0}]?467 Kč/u', $text);
        // "kg/ks" hint only for ks products without turkey weights
        self::assertStringContainsString('0.3 kg/ks', $text);
        self::assertSame(1, substr_count($text, 'kg/ks'));
    }

    private function boxxiForm(int $orderId): Form
    {
        $crawler = $this->client->request('GET', sprintf('/admin/objednavky/%d/boxxi', $orderId));
        self::assertResponseIsSuccessful();

        return $this->buttonForm($crawler, 'Uložit a propsat');
    }
}
