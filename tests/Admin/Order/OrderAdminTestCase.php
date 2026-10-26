<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use CeskaKruta\Web\Tests\Support\HttpMock;
use CeskaKruta\Web\Tests\Unit\Pohoda\GoldenOrders;
use CeskaKruta\Web\Tests\Unit\Pohoda\XmlAssert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Orders section (PLAN §2.17, §4.6). Fixture orders: tests/database/fixtures/50-orders.sql
 * (5901–5906 hand-made = golden file data, 5001–5060 "Strankovani NN" for pagination). The 6 hand-made orders have the
 * highest ids, so they are on the first (unfiltered) list page.
 */
abstract class OrderAdminTestCase extends AdminWebTestCase
{
    protected const LIST_URL = '/admin/objednavky';

    protected const SHOP_ORDER = 5901;
    protected const DELIVERY_ORDER = 5902;
    protected const COOL_BALIK_SPLIT_ORDER = 5903;
    protected const COUPON_ORDER = 5904;
    protected const COOL_BALIK_ORDER = 5905;
    protected const SYNCED_ORDER = 5906;

    protected const POHODA_URL = 'http://pohoda.test/xml';
    protected const POHODA_AUTHORIZATION = 'test-pohoda-auth';
    protected const BOXXI_URL = 'https://api.boxxi-logistics.cz/v1/shipments';
    protected const BOXXI_TOKEN = 'test-token';

    /**
     * @param array<string, string> $query
     */
    protected function requestList(array $query = []): Crawler
    {
        $crawler = $this->client->request('GET', self::LIST_URL . ($query !== [] ? '?' . http_build_query($query) : ''));
        self::assertResponseIsSuccessful();

        return $crawler;
    }

    protected function row(Crawler $crawler, int $orderId): Crawler
    {
        $row = $crawler->filter('#item-' . $orderId);
        self::assertCount(1, $row, sprintf('Order row #item-%d not found.', $orderId));

        return $row;
    }

    /**
     * Parsed CSV rows in file order (header included), optional UTF-8 BOM ignored.
     *
     * @return list<array<int, null|string>>
     */
    protected function csvRows(string $csv): array
    {
        $csv = (string) preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $rows = [];

        foreach (preg_split('/\r\n|\n|\r/', trim($csv)) ?: [] as $line) {
            $rows[] = str_getcsv($line, ',', '"', '\\');
        }

        return $rows;
    }

    /**
     * Sends one order to Pohoda via its "Propsat" form on the list page.
     */
    protected function sendToPohoda(int $orderId): void
    {
        $crawler = $this->requestList();
        $form = $crawler->filter(sprintf('form[action="/admin/objednavky/%d/pohoda"]', $orderId));
        self::assertCount(1, $form, sprintf('Pohoda form for order %d not found on the list.', $orderId));
        self::assertSame('POST', $form->form()->getMethod());

        $this->client->submit($form->form());
    }

    /**
     * Follows the redirect after an action and asserts it went back to the order list.
     */
    protected function followRedirectToList(): Crawler
    {
        self::assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('~^(https?://[^/]+)?/admin/objednavky(\?[^#]*)?(#item-\d+)?$~', $location);

        return $this->client->followRedirect();
    }

    protected function pohodaResponse(string $name): MockResponse
    {
        return new MockResponse(GoldenOrders::file('pohoda/responses/' . $name . '.xml'), [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'text/xml'],
        ]);
    }

    /**
     * @return list<array{method: string, url: string, body: string, headers: list<string>}>
     */
    protected function requests(): array
    {
        return HttpMock::requests();
    }

    /**
     * @param array{method: string, url: string, body: string, headers: list<string>} $request
     */
    protected function assertPohodaRequest(array $request, null|string $goldenFile = null): void
    {
        self::assertSame('POST', $request['method']);
        self::assertSame(self::POHODA_URL, $request['url']);
        self::assertSame(self::POHODA_AUTHORIZATION, $this->header($request, 'STW-Authorization'));
        self::assertStringContainsString('text/xml', (string) $this->header($request, 'Content-Type'));

        if ($goldenFile !== null) {
            XmlAssert::assertSameXml(GoldenOrders::file('pohoda/' . $goldenFile), $request['body'], $goldenFile);
        }
    }

    /**
     * @param array{method: string, url: string, body: string, headers: list<string>} $request
     */
    protected function header(array $request, string $name): null|string
    {
        foreach ($request['headers'] as $header) {
            [$headerName, $value] = array_map('trim', explode(':', $header, 2)) + [1 => ''];

            if (strtolower($headerName) === strtolower($name)) {
                return $value;
            }
        }

        return null;
    }

    protected function pohodaSyncDate(int $orderId): mixed
    {
        return $this->fetchRow('order', $orderId)['pohoda_sync_dt'];
    }

    protected function buttonForm(Crawler $crawler, string $label): \Symfony\Component\DomCrawler\Form
    {
        $button = $crawler->selectButton($label);
        self::assertGreaterThan(0, $button->count(), sprintf('Button "%s" not found.', $label));

        return $button->form();
    }

    protected function link(Crawler $crawler, string $label): \Symfony\Component\DomCrawler\Link
    {
        $link = $crawler->selectLink($label);
        self::assertGreaterThan(0, $link->count(), sprintf('Link "%s" not found.', $label));

        return $link->link();
    }

    protected function inputValue(Crawler $crawler, string $selector): string
    {
        $input = $crawler->filter($selector);
        self::assertCount(1, $input, sprintf('Input "%s" not found.', $selector));

        return (string) $input->attr('value');
    }

    protected function fieldValue(\Symfony\Component\DomCrawler\Form $form, string $name): string
    {
        self::assertTrue($form->has($name), sprintf('Form field "%s" not found.', $name));
        $field = $form->get($name);
        self::assertInstanceOf(\Symfony\Component\DomCrawler\Field\FormField::class, $field);
        $value = $field->getValue();
        self::assertIsString($value);

        return $value;
    }

    protected function responseHeader(string $name): string
    {
        $value = $this->client->getInternalResponse()->getHeader($name);
        self::assertIsString($value, sprintf('Response header "%s" missing.', $name));

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function numeric(array $row, string $column): float
    {
        self::assertIsNumeric($row[$column] ?? null, sprintf('Column "%s" is not numeric.', $column));

        return (float) $row[$column];
    }

    /**
     * Normalized text of the whole page (whitespace collapsed).
     */
    protected function pageText(Crawler $crawler): string
    {
        $body = $crawler->filter('body');
        self::assertCount(1, $body);

        return $body->text();
    }
}
