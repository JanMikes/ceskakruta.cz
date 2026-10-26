<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

/**
 * Order list `/admin/objednavky` (PLAN §2.17, old `app/components/OrdersList`).
 * Filter = plain GET params `date_from`, `date_to` (YYYY-MM-DD, on `order.date`), `customer_name` (LIKE), `page` (PLAN §3.4).
 */
final class OrderListTest extends OrderAdminTestCase
{
    /** 5906 … 5901 followed by the pagination orders 5060 … 5017 = exactly 50 rows */
    private const FIRST_PAGE = [5906, 5905, 5904, 5903, 5902, 5901,
        5060, 5059, 5058, 5057, 5056, 5055, 5054, 5053, 5052, 5051, 5050, 5049, 5048, 5047, 5046, 5045, 5044, 5043, 5042, 5041,
        5040, 5039, 5038, 5037, 5036, 5035, 5034, 5033, 5032, 5031, 5030, 5029, 5028, 5027, 5026, 5025, 5024, 5023, 5022, 5021,
        5020, 5019, 5018, 5017];

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testListSortedByIdDesc(): void
    {
        $crawler = $this->requestList();

        self::assertSelectorTextContains('h1', 'Objednávky');
        $this->assertListedInOrder($crawler, [5906, 5905, 5904, 5903, 5902, 5901, 5060, 5059]);

        // Base fixture orders are further down (page 2)
        $crawler = $this->requestList(['page' => '2']);
        $this->assertListedInOrder($crawler, [5016, 5001, 2, 1]);
    }

    public function testColumns(): void
    {
        $crawler = $this->requestList();

        // Č. + "Nový web" badge (source set), Datum provedení (ins_dt), Zákazník, Cena, Místo, Datum, Poznámka, Pohoda unsynced
        $shop = $this->row($crawler, self::SHOP_ORDER)->text();
        self::assertStringContainsString('5901', $shop);
        self::assertStringContainsString('Nový web', $shop);
        self::assertStringContainsString('10.06.2024 08:15', $shop);
        self::assertStringContainsString('Jana Nováková', $shop);
        self::assertStringContainsString('845 Kč', $shop);
        self::assertStringContainsString('Rychvald - Prodejna', $shop);
        self::assertStringContainsString('14.06.2024', $shop);
        self::assertStringContainsString('Vyzvednu odpoledne', $shop);
        self::assertStringContainsString('Nepropsáno', $shop);
        self::assertStringNotContainsString('Sleva', $shop);
        self::assertCount(1, $this->row($crawler, self::SHOP_ORDER)->filter('form[action="/admin/objednavky/5901/pohoda"]'));
        self::assertCount(1, $this->row($crawler, self::SHOP_ORDER)->filter('a[href="/admin/objednavky/5901"]'), 'Detail link');

        // No badge without source; delivery address street + "city zip"
        $delivery = $this->row($crawler, self::DELIVERY_ORDER)->text();
        self::assertStringNotContainsString('Nový web', $delivery);
        self::assertStringContainsString('Petr Svoboda', $delivery);
        self::assertStringContainsString('Hlavní 12', $delivery);
        self::assertStringContainsString('Přerov 75002', $delivery);
        self::assertStringContainsString('Rozvoz domů Přerov', $delivery);

        // Coupon
        $coupon = $this->row($crawler, self::COUPON_ORDER)->text();
        self::assertStringContainsString('(Sleva -10%)', $coupon);
        self::assertStringContainsString('520 Kč', $coupon);

        // Synced to Pohoda (datetime instead of the button) and to Boxxi (datetime + cool_balik_price)
        $synced = $this->row($crawler, self::SYNCED_ORDER);
        self::assertStringContainsString('15.06.2024 09:00:00', $synced->text());
        self::assertStringContainsString('20.06.2024 10:30:00', $synced->text());
        self::assertStringContainsString('1500 Kč', $synced->text());
        self::assertStringNotContainsString('Nepropsáno', $synced->text());
        self::assertCount(0, $synced->filter('form[action="/admin/objednavky/5906/pohoda"]'));
        self::assertCount(0, $synced->filter('a[href="/admin/objednavky/5906/boxxi"]'));
    }

    public function testFilterByDateRange(): void
    {
        $crawler = $this->requestList(['date_from' => '2024-06-18', 'date_to' => '2024-06-21']);

        // order.date: 5906 + 5903 = 21st, 5902 = 19th, 5904 + base order 2 = 18th (both bounds inclusive)
        $this->assertListedInOrder($crawler, [5906, 5904, 5903, 5902, 2]);

        $listed = $this->listedIds($crawler);
        self::assertNotContains(self::SHOP_ORDER, $listed, '14th is before date_from');
        self::assertNotContains(self::COOL_BALIK_ORDER, $listed, '25th is after date_to');
        self::assertNotContains(1, $listed);
        self::assertNotContains(5001, $listed);
    }

    public function testFilterByCustomerName(): void
    {
        $crawler = $this->requestList();

        $form = $this->buttonForm($crawler, 'Filtrovat');
        self::assertSame('GET', $form->getMethod());
        $form['customer_name'] = 'Dvořák';
        $crawler = $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSame('Dvořák', $this->client->getRequest()->query->get('customer_name'));
        self::assertSame([self::COOL_BALIK_SPLIT_ORDER], $this->listedIds($crawler));
        // Filter values stay in the form
        self::assertSame('Dvořák', $this->inputValue($crawler, 'input[name="customer_name"]'));
    }

    public function testReset(): void
    {
        $crawler = $this->requestList(['customer_name' => 'Dvořák', 'date_from' => '2024-06-01']);
        self::assertSame([self::COOL_BALIK_SPLIT_ORDER], $this->listedIds($crawler));

        $link = $this->link($crawler, 'Resetovat');
        self::assertSame(self::LIST_URL, (string) parse_url($link->getUri(), PHP_URL_PATH));
        self::assertNull(parse_url($link->getUri(), PHP_URL_QUERY), 'Reset link must drop all filter params.');

        $crawler = $this->client->click($link);

        self::assertContains(self::SHOP_ORDER, $this->listedIds($crawler));
        self::assertSame('', $this->inputValue($crawler, 'input[name="customer_name"]'));
    }

    public function testCheckboxOnlyForUnsynced(): void
    {
        $crawler = $this->requestList();

        self::assertCount(1, $this->row($crawler, self::SHOP_ORDER)->filter('input[type="checkbox"][name="orders[]"][value="5901"]'));
        self::assertCount(1, $this->row($crawler, self::COOL_BALIK_ORDER)->filter('input[type="checkbox"][name="orders[]"][value="5905"]'));
        self::assertCount(0, $this->row($crawler, self::SYNCED_ORDER)->filter('input[type="checkbox"]'));

        // The checkboxes belong to the bulk form "Označené poslat do Pohody" (POST /admin/objednavky/pohoda)
        $bulk = $this->buttonForm($crawler, 'Označené poslat do Pohody');
        self::assertSame('POST', $bulk->getMethod());
        self::assertSame('/admin/objednavky/pohoda', (string) parse_url($bulk->getUri(), PHP_URL_PATH));
        self::assertTrue($bulk->has('orders'), 'Bulk form must contain the orders[] checkboxes.');
        self::assertTrue($bulk->has('_token'));

        $values = [];
        $fields = $bulk->get('orders');
        foreach (is_array($fields) ? $fields : [$fields] as $field) {
            self::assertInstanceOf(\Symfony\Component\DomCrawler\Field\ChoiceFormField::class, $field);
            $values = [...$values, ...$field->availableOptionValues()];
        }
        self::assertContains('5901', $values);
        self::assertNotContains('5906', $values);
    }

    public function testBoxxiColumnOnlyForCoolBalik(): void
    {
        $crawler = $this->requestList();

        // CoolBalík unsynced: Pohoda + Boxxi "Nepropsáno", Boxxi "Propsat" links to the Boxxi page
        $coolBalik = $this->row($crawler, self::COOL_BALIK_SPLIT_ORDER);
        self::assertSame(2, substr_count($coolBalik->text(), 'Nepropsáno'));
        self::assertCount(1, $coolBalik->filter('a[href="/admin/objednavky/5903/boxxi"]'));

        // Not CoolBalík (shop, own delivery): no Boxxi state at all
        foreach ([self::SHOP_ORDER, self::DELIVERY_ORDER] as $orderId) {
            $row = $this->row($crawler, $orderId);
            self::assertSame(1, substr_count($row->text(), 'Nepropsáno'), 'Only the Pohoda state, no Boxxi state.');
            self::assertCount(0, $row->filter(sprintf('a[href="/admin/objednavky/%d/boxxi"]', $orderId)));
        }
    }

    public function testPaginates50PerPage(): void
    {
        $crawler = $this->requestList();
        self::assertSame(self::FIRST_PAGE, $this->listedIds($crawler));

        $pageTwo = $this->listedIds($this->requestList(['page' => '2']));
        self::assertNotEmpty($pageTwo);
        self::assertSame(array_slice(range(5016, 5001), 0, 16), array_slice($pageTwo, 0, 16));
        self::assertSame([], array_intersect(self::FIRST_PAGE, $pageTwo));
        self::assertLessThan(min(self::FIRST_PAGE), max($pageTwo));
    }

    public function testPaginationKeepsFilter(): void
    {
        $crawler = $this->requestList(['customer_name' => 'Strankovani']);
        self::assertSame(range(5060, 5011), $this->listedIds($crawler));

        $links = $crawler->filter('a[href*="page=2"]');
        self::assertGreaterThan(0, $links->count(), 'Link to page 2 not found.');
        $href = (string) $links->first()->attr('href');
        self::assertStringContainsString('customer_name=Strankovani', $href);

        $crawler = $this->client->click($links->first()->link());

        self::assertResponseIsSuccessful();
        self::assertSame(range(5010, 5001), $this->listedIds($crawler));
    }
}
