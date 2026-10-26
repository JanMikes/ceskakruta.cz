<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

/**
 * CSV export `/admin/objednavky/export.csv` (old `OrderPresenter::actionExport`, League CSV defaults: `,` + `"`).
 * Extra (decided, PLAN §2.17): respects the list filter, always all pages.
 */
final class OrderExportTest extends OrderAdminTestCase
{
    private const HEADER = ['Číslo', 'Datum provedení', 'Zákazník', 'Cena', 'Místo', 'Datum', 'Poznámka'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testCsvHeaderRowsAndFilename(): void
    {
        $this->client->request('GET', '/admin/objednavky/export.csv');

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/csv', $this->responseHeader('Content-Type'));
        $disposition = $this->responseHeader('Content-Disposition');
        self::assertStringStartsWith('attachment', $disposition);
        self::assertMatchesRegularExpression('/filename="?ceska-kruta-objednavky\.csv"?/', $disposition);

        $rows = $this->csvRows($this->client->getInternalResponse()->getContent());
        self::assertSame(self::HEADER, $rows[0]);

        $byId = $this->rowsById($rows);
        // Zákazník = "name, street, city zip"; Datum provedení = ins_dt d.m.Y H:i; Datum = date d.m.Y
        self::assertSame(['5902', '12.06.2024 19:40', 'Petr Svoboda, Hlavní 12, Přerov 75002', '1728', 'Rozvoz domů Přerov', '19.06.2024', ''], $byId[5902]);
        self::assertSame(['5903', '14.06.2024 07:05', 'Marie Dvořáková, Školní 7, Olomouc 77900', '1467', 'Rozvoz CoolBalík', '21.06.2024', 'Doručit po 16. hodině'], $byId[5903]);
        self::assertSame('Zvonit u "Černých" & nechat u dveří', $byId[5905][6], 'Quotes in the note must survive CSV quoting.');
        self::assertStringStartsWith('Jana Nováková, ', (string) $byId[5901][2]);
        self::assertSame('Rychvald - Prodejna', $byId[5901][4]);
    }

    public function testExportRespectsFilter(): void
    {
        // The "CSV export" link of the filtered list carries the filter
        $crawler = $this->requestList(['customer_name' => 'Strankovani']);
        $link = $this->link($crawler, 'CSV export');
        self::assertSame('/admin/objednavky/export.csv', (string) parse_url($link->getUri(), PHP_URL_PATH));

        $this->client->click($link);
        self::assertResponseIsSuccessful();

        $ids = array_keys($this->rowsById($this->csvRows($this->client->getInternalResponse()->getContent())));
        self::assertSame(range(5060, 5001), $ids, 'All 60 filtered orders (more than one list page), id DESC, nothing else.');

        $this->client->request('GET', '/admin/objednavky/export.csv?' . http_build_query(['date_from' => '2024-06-18', 'date_to' => '2024-06-21']));
        self::assertResponseIsSuccessful();
        $ids = array_keys($this->rowsById($this->csvRows($this->client->getInternalResponse()->getContent())));
        self::assertSame([5906, 5904, 5903, 5902, 2], array_values(array_intersect($ids, [5906, 5905, 5904, 5903, 5902, 5901, 2, 1])));
        self::assertNotContains(5001, $ids);
    }

    public function testExportIncludesAllPages(): void
    {
        $this->client->request('GET', '/admin/objednavky/export.csv');
        self::assertResponseIsSuccessful();

        $ids = array_keys($this->rowsById($this->csvRows($this->client->getInternalResponse()->getContent())));

        self::assertGreaterThan(50, count($ids));
        foreach ([5906, 5901, 5060, 5001, 2, 1] as $id) {
            self::assertContains($id, $ids);
        }

        $sorted = $ids;
        rsort($sorted);
        self::assertSame($sorted, $ids, 'Export is sorted by id DESC.');
    }

    /**
     * @param list<array<int, null|string>> $rows
     * @return array<int, array<int, null|string>> data rows keyed by order id, in file order
     */
    private function rowsById(array $rows): array
    {
        $byId = [];

        foreach (array_slice($rows, 1) as $row) {
            $byId[(int) $row[0]] = $row;
        }

        return $byId;
    }
}
