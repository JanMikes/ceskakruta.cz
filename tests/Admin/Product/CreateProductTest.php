<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Product;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.9 form (create): name (required, max 50), type (required 1|2), unit (ks|kg, default kg),
 * weight_per_unit (float, default 1), price (int), code, can_be_sliced_flag, can_be_packed_flag, force_packing,
 * price_pack (optional int), text_additional, text, packages_size.
 */
final class CreateProductTest extends AdminWebTestCase
{
    use SubmitsAdminForms;

    public function testCreates(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/pridat');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, [
            'product[name]' => 'Krůtí křídla test',
            'product[type]' => '1',
            'product[unit]' => 'ks',
            'product[weight_per_unit]' => '0.75',
            'product[price]' => '159',
            'product[code]' => '300300',
            'product[can_be_sliced_flag]' => '1',
            'product[can_be_packed_flag]' => '1',
            'product[force_packing]' => '1',
            'product[price_pack]' => '12',
            'product[text_additional]' => '<p>Pod čarou</p>',
            'product[text]' => '<p>Křídla</p>',
            'product[packages_size]' => '1000, 500, 500',
        ]);

        $id = $this->lastInsertedId('product');
        $this->assertRedirectsTo('/admin/produkty', $id);

        $row = $this->fetchRow('product', $id);
        self::assertSame('Krůtí křídla test', $row['name']);
        self::assertSame(1, $this->asInt($row['type']));
        self::assertSame('ks', $row['unit']);
        self::assertEqualsWithDelta(0.75, $this->asFloat($row['weight_per_unit']), 0.0001);
        self::assertSame(159, $this->asInt($row['price']));
        self::assertSame('300300', $row['code']);
        self::assertSame(1, $this->asInt($row['can_be_sliced_flag']));
        self::assertSame(1, $this->asInt($row['can_be_packed_flag']));
        self::assertSame(1, $this->asInt($row['force_packing']));
        self::assertSame(12, $this->asInt($row['price_pack']));
        self::assertSame('<p>Pod čarou</p>', $row['text_additional']);
        self::assertSame('<p>Křídla</p>', $row['text']);
        self::assertSame('[500,1000]', $row['packages_size']);
        self::assertNull($row['half_of_product_id']);
        self::assertSame(1, $this->asInt($row['active_flag']));
        self::assertSame(0, $this->asInt($row['del_flag']));
        self::assertSame('admin', $row['ins_process']);
        self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);
        self::assertNotEmpty($row['ins_dt']);

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertSelectorExists('#item-' . $id);
    }

    public function testAlwaysWritesUnit(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/pridat');
        self::assertResponseIsSuccessful();

        // Defaults rendered in the form (old ManageProductFormFactory: unit kg, weight 1)
        self::assertSame('kg', $this->fieldValue($crawler, 'product[unit]'));
        self::assertSame('1', $this->fieldValue($crawler, 'product[weight_per_unit]'));

        // Only required fields filled; `product.unit` is NOT NULL without a default in production → must always be written
        $this->submitAdminForm($crawler, [
            'product[name]' => 'Minimální produkt',
            'product[type]' => '2',
            'product[price]' => '50',
        ]);

        $id = $this->lastInsertedId('product');
        $this->assertRedirectsTo('/admin/produkty', $id);

        $row = $this->fetchRow('product', $id);
        self::assertSame('kg', $row['unit']);
        self::assertEqualsWithDelta(1.0, $this->asFloat($row['weight_per_unit']), 0.0001);
        self::assertSame('[]', $row['packages_size']);
        self::assertNull($row['price_pack']);
        self::assertSame(0, $this->asInt($row['can_be_packed_flag']));
        self::assertSame(0, $this->asInt($row['can_be_sliced_flag']));
        self::assertSame(0, $this->asInt($row['force_packing']));
    }

    /**
     * @param array<string, string> $fields
     * @param list<string> $omit
     */
    #[DataProvider('provideInvalidData')]
    public function testValidation(array $fields, array $omit): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/pridat');
        self::assertResponseIsSuccessful();

        $countBefore = $this->fetchInt('SELECT COUNT(*) FROM product');

        $this->submitAdminForm($crawler, $fields + [
            'product[name]' => 'Validní název',
            'product[type]' => '1',
            'product[unit]' => 'kg',
            'product[weight_per_unit]' => '1',
            'product[price]' => '100',
        ], $omit);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($countBefore, $this->fetchInt('SELECT COUNT(*) FROM product'));
    }

    /**
     * @return iterable<string, array{array<string, string>, list<string>}>
     */
    public static function provideInvalidData(): iterable
    {
        yield 'name empty' => [['product[name]' => ''], []];
        yield 'name too long' => [['product[name]' => str_repeat('a', 51)], []];
        yield 'type missing' => [[], ['product[type]']];
        yield 'type not 1 or 2' => [['product[type]' => '3'], []];
        yield 'unit invalid' => [['product[unit]' => 'g'], []];
        yield 'weight not a number' => [['product[weight_per_unit]' => 'abc'], []];
        yield 'price not an integer' => [['product[price]' => '12.5'], []];
        yield 'price text' => [['product[price]' => 'abc'], []];
        yield 'price_pack not an integer' => [['product[can_be_packed_flag]' => '1', 'product[price_pack]' => 'abc'], []];
        yield 'packages_size invalid' => [['product[packages_size]' => '500, abc'], []];
    }

    public function testDecimalWeightAccepted(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/pridat');
        self::assertResponseIsSuccessful();

        $this->submitAdminForm($crawler, [
            'product[name]' => 'Šunka 1.5',
            'product[type]' => '2',
            'product[unit]' => 'ks',
            'product[weight_per_unit]' => '1.5',
            'product[price]' => '99',
        ]);

        $id = $this->lastInsertedId('product');
        $this->assertRedirectsTo('/admin/produkty', $id);
        self::assertEqualsWithDelta(1.5, $this->asFloat($this->fetchRow('product', $id)['weight_per_unit']), 0.0001);
    }
}
