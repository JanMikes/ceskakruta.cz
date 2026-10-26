<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Order;

use CeskaKruta\Web\Tests\Support\HttpMock;
use CeskaKruta\Web\Tests\Unit\Pohoda\XmlAssert;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Pohoda single send `POST /admin/objednavky/{id}/pohoda` and bulk send `POST /admin/objednavky/pohoda` with `orders[]`
 * (PLAN §4.6). Note for the implementation: the per-row "Propsat" forms must not be nested inside the bulk form
 * (invalid HTML, parsers drop nested forms) → e.g. checkboxes with `form="…"` attribute or the bulk form outside the table.
 */
final class PohodaControllerTest extends OrderAdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testSingleSend(): void
    {
        HttpMock::queue($this->pohodaResponse('order-ok'));

        $this->sendToPohoda(self::SHOP_ORDER);

        self::assertCount(1, $this->requests());
        self::assertNotNull($this->pohodaSyncDate(self::SHOP_ORDER));

        $crawler = $this->followRedirectToList();
        $this->assertFlash('Objednávka propsána do Pohody');
        self::assertStringNotContainsString('Nepropsáno', $this->row($crawler, self::SHOP_ORDER)->text());
    }

    public function testSingleSendRequiresCsrfToken(): void
    {
        $this->client->request('POST', '/admin/objednavky/5901/pohoda', ['_token' => 'invalid']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame([], $this->requests());
        self::assertNull($this->pohodaSyncDate(self::SHOP_ORDER));
    }

    public function testBulkSendOnlyChecked(): void
    {
        HttpMock::queue($this->pohodaResponse('order-ok'), $this->pohodaResponse('order-ok'));

        $this->client->submit($this->bulkForm([self::SHOP_ORDER, self::COUPON_ORDER]));

        $requests = $this->requests();
        self::assertCount(2, $requests);
        $orderNumbers = array_map(static fn (array $request): null|string => XmlAssert::text($request['body'], '//ord:numberOrder'), $requests);
        sort($orderNumbers);
        self::assertSame(['WEB5901', 'WEB5904'], $orderNumbers);
        foreach ($requests as $request) {
            $this->assertPohodaRequest($request);
        }

        self::assertNotNull($this->pohodaSyncDate(self::SHOP_ORDER));
        self::assertNotNull($this->pohodaSyncDate(self::COUPON_ORDER));
        // Unchecked orders untouched
        self::assertNull($this->pohodaSyncDate(self::DELIVERY_ORDER));
        self::assertNull($this->pohodaSyncDate(5060));

        $this->followRedirectToList();
        self::assertSelectorExists('.alert-success');
        self::assertSelectorNotExists('.alert-danger');
    }

    public function testBulkContinuesAfterFailureAndReports(): void
    {
        // First processed order fails (transport error), the second one must still be sent
        HttpMock::queue(new MockResponse('', ['error' => 'Connection refused']), $this->pohodaResponse('order-ok'));

        $this->client->submit($this->bulkForm([self::SHOP_ORDER, self::COUPON_ORDER]));

        self::assertCount(2, $this->requests(), 'Bulk send continues after a failed order.');

        $synced = array_values(array_filter(
            [self::SHOP_ORDER, self::COUPON_ORDER],
            fn (int $id): bool => $this->pohodaSyncDate($id) !== null,
        ));
        self::assertCount(1, $synced, 'Exactly one of the two orders is synced.');
        $failed = $synced === [self::SHOP_ORDER] ? self::COUPON_ORDER : self::SHOP_ORDER;

        $this->followRedirectToList();
        $this->assertFlash((string) $failed, 'danger');
    }

    /**
     * Bulk form of the list page with exactly the given orders ticked.
     *
     * @param list<int> $orderIds
     */
    private function bulkForm(array $orderIds): Form
    {
        $form = $this->buttonForm($this->requestList(), 'Označené poslat do Pohody');
        self::assertTrue($form->has('orders'), 'Bulk form has no orders[] checkboxes.');

        $fields = $form->get('orders');
        $ticked = [];

        foreach (is_array($fields) ? $fields : [$fields] as $field) {
            self::assertInstanceOf(ChoiceFormField::class, $field);
            $value = $field->availableOptionValues()[0] ?? null;

            if (in_array((int) $value, $orderIds, true)) {
                $field->tick();
                $ticked[] = (int) $value;
            } else {
                $field->untick();
            }
        }

        sort($ticked);
        self::assertSame($orderIds, $ticked, 'Checkboxes of the orders to send not found.');

        return $form;
    }
}
