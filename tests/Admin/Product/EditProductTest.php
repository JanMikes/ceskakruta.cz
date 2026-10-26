<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Product;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.9 edit: type 1/2 full form; type 3/4 only price, code, text (everything else untouched);
 * empty price_pack → NULL; can_be_packed_flag = false → force_packing = false; packages_size JSON `[500,1000,1500]`.
 */
final class EditProductTest extends AdminWebTestCase
{
    use SubmitsAdminForms;

    public function testEditsMeatProduct(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        // Prefilled from the DB
        self::assertSame('Krůtí stehno test', $this->fieldValue($crawler, 'product[name]'));
        self::assertSame('1', $this->fieldValue($crawler, 'product[type]'));
        self::assertSame('kg', $this->fieldValue($crawler, 'product[unit]'));
        self::assertSame('199', $this->fieldValue($crawler, 'product[price]'));
        self::assertSame('300100', $this->fieldValue($crawler, 'product[code]'));
        self::assertSame('20', $this->fieldValue($crawler, 'product[price_pack]'));
        self::assertSame('250, 500', $this->fieldValue($crawler, 'product[packages_size]'));
        self::assertCount(1, $crawler->filter('input[name="product[can_be_packed_flag]"][checked]'));
        self::assertCount(1, $crawler->filter('input[name="product[force_packing]"][checked]'));

        $this->submitAdminForm($crawler, [
            'product[name]' => 'Krůtí stehno upravené',
            'product[type]' => '2',
            'product[unit]' => 'ks',
            'product[weight_per_unit]' => '0.5',
            'product[price]' => '210',
            'product[code]' => '300199',
            'product[price_pack]' => '25',
            'product[text_additional]' => '<p>Nový text pod čarou</p>',
            'product[text]' => '<p>Nový popis</p>',
            'product[packages_size]' => '250, 500',
        ], omit: ['product[can_be_sliced_flag]']);

        $this->assertRedirectsTo('/admin/produkty', 3001);

        $row = $this->fetchRow('product', 3001);
        self::assertSame('Krůtí stehno upravené', $row['name']);
        self::assertSame(2, $this->asInt($row['type']));
        self::assertSame('ks', $row['unit']);
        self::assertEqualsWithDelta(0.5, $this->asFloat($row['weight_per_unit']), 0.0001);
        self::assertSame(210, $this->asInt($row['price']));
        self::assertSame('300199', $row['code']);
        self::assertSame(0, $this->asInt($row['can_be_sliced_flag']));
        self::assertSame(1, $this->asInt($row['can_be_packed_flag']));
        self::assertSame(1, $this->asInt($row['force_packing']));
        self::assertSame(25, $this->asInt($row['price_pack']));
        self::assertSame('<p>Nový text pod čarou</p>', $row['text_additional']);
        self::assertSame('<p>Nový popis</p>', $row['text']);
        self::assertSame('[250,500]', $row['packages_size']);
        self::assertSame(3020, $this->asInt($row['order']), 'Order is not part of the form');
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['product[price]' => '205'], button: 'Uložit a pokračovat v editaci');

        $this->assertRedirectsTo('/admin/produkty/3001');
        self::assertSame(205, $this->asInt($this->fetchRow('product', 3001)['price']));
    }

    public function testUnknownIdRedirectsToList(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/produkty/3999');

        $this->assertRedirectsTo('/admin/produkty');
    }

    #[DataProvider('provideTurkeyProducts')]
    public function testTurkeyProductOnlyPriceCodeText(int $productId): void
    {
        $before = $this->arrangeTurkeyProduct($productId);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/' . $productId);
        self::assertResponseIsSuccessful();

        foreach (['name', 'type', 'unit', 'weight_per_unit', 'can_be_sliced_flag', 'can_be_packed_flag', 'force_packing', 'price_pack', 'text_additional', 'packages_size'] as $field) {
            $this->assertFieldNotRendered($crawler, sprintf('product[%s]', $field));
        }

        $this->submitAdminForm($crawler, [
            'product[price]' => '135',
            'product[code]' => 'KRUTA-NEW',
            'product[text]' => '<p>Nový popis krůty</p>',
        ]);

        $this->assertRedirectsTo('/admin/produkty', $productId);

        $row = $this->fetchRow('product', $productId);
        self::assertSame(135, $this->asInt($row['price']));
        self::assertSame('KRUTA-NEW', $row['code']);
        self::assertSame('<p>Nový popis krůty</p>', $row['text']);
        $this->assertUntouchedTurkeyColumns($before, $row);
    }

    /**
     * Fields that are not rendered for type 3/4 must be ignored even when posted (forged request).
     */
    #[DataProvider('provideTurkeyProducts')]
    public function testTurkeyProductIgnoresPostedProtectedFields(int $productId): void
    {
        $before = $this->arrangeTurkeyProduct($productId);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/' . $productId);
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, [
            'product[price]' => '135',
            'product[name]' => 'Hacked',
            'product[type]' => '1',
            'product[unit]' => 'ks',
            'product[weight_per_unit]' => '9',
            'product[can_be_sliced_flag]' => '1',
            'product[can_be_packed_flag]' => '0',
            'product[force_packing]' => '0',
            'product[price_pack]' => '',
            'product[text_additional]' => '<p>Hacked</p>',
            'product[packages_size]' => '100',
            'product[half_of_product_id]' => '',
        ]);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        $this->assertUntouchedTurkeyColumns($before, $this->fetchRow('product', $productId));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideTurkeyProducts(): iterable
    {
        yield 'type 3 half turkey' => [2];
        yield 'type 4 half tom' => [48];
    }

    public function testEmptyPricePackIsNull(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['product[price_pack]' => '']);

        $this->assertRedirectsTo('/admin/produkty', 3001);
        self::assertNull($this->fetchRow('product', 3001)['price_pack']);
    }

    public function testUnpackableClearsForcePacking(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        // force_packing stays ticked in the request, but packing is not possible
        $this->submitAdminForm($crawler, ['product[force_packing]' => '1'], omit: ['product[can_be_packed_flag]']);

        $this->assertRedirectsTo('/admin/produkty', 3001);

        $row = $this->fetchRow('product', 3001);
        self::assertSame(0, $this->asInt($row['can_be_packed_flag']));
        self::assertSame(0, $this->asInt($row['force_packing']));
    }

    public function testEditsPackagesSize(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['product[packages_size]' => '1500, 500, 1000, 500']);

        $this->assertRedirectsTo('/admin/produkty', 3001);

        $stored = $this->fetchRow('product', 3001)['packages_size'];
        self::assertIsString($stored);
        // Same format as existing rows, readable by the frontend (src/Query/GetProducts.php json_decode → array<int>)
        self::assertSame('[500,1000,1500]', $stored);
    }

    public function testEmptyPackagesSizeStoresEmptyArray(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['product[packages_size]' => '']);

        $this->assertRedirectsTo('/admin/produkty', 3001);
        self::assertSame('[]', $this->fetchRow('product', 3001)['packages_size']);
    }

    #[DataProvider('provideInvalidPackagesSize')]
    public function testRejectsInvalidPackagesSize(string $input): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, ['product[packages_size]' => $input, 'product[price]' => '333']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');

        $row = $this->fetchRow('product', 3001);
        self::assertSame('[250,500]', $row['packages_size']);
        self::assertSame(199, $this->asInt($row['price']), 'Nothing is saved when the form is invalid');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidPackagesSize(): iterable
    {
        yield 'text' => ['abc'];
        yield 'zero' => ['0'];
        yield 'negative' => ['500, -100'];
        yield 'decimal' => ['500, 1.5'];
    }

    /**
     * Gives the turkey product non-default values in every column the type 3/4 form must not touch.
     *
     * @return array<string, mixed>
     */
    private function arrangeTurkeyProduct(int $productId): array
    {
        $this->connection()->update('product', [
            'unit' => 'kg',
            'weight_per_unit' => 2.5,
            'can_be_packed_flag' => 1,
            'can_be_sliced_flag' => 1,
            'force_packing' => 1,
            'price_pack' => 33,
            'packages_size' => '[700]',
            'text_additional' => '<p>Pod čarou krůta</p>',
        ], ['id' => $productId]);

        return $this->fetchRow('product', $productId);
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    private function assertUntouchedTurkeyColumns(array $before, array $after): void
    {
        foreach (['name', 'type', 'unit', 'weight_per_unit', 'can_be_packed_flag', 'can_be_sliced_flag', 'force_packing', 'price_pack', 'packages_size', 'text_additional', 'half_of_product_id', 'order', 'active_flag', 'del_flag'] as $column) {
            self::assertEquals($before[$column], $after[$column], sprintf('Column "%s" must not change for turkey products.', $column));
        }
    }
}
