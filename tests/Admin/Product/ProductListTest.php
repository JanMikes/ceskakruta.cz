<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Product;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.9 list: sorted `order DESC`; Název, "{price} Kč", "Recepty (n)" (non-deleted recipes) → recipes list,
 * [A]/[D] + [X] hidden for type 3/4, [M], Edit, [AA].
 */
final class ProductListTest extends AdminWebTestCase
{
    public function testColumnsAndRecipeCount(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Maso a výrobky');

        // order DESC: 3001 (3020) > 3002 (3010) > 1 (900) > 100 (500) > 101 (400)
        $this->assertListedInOrder($crawler, [3001, 3002, 1, 100, 101]);

        $row = $crawler->filter('#item-3001');
        self::assertCount(1, $row);
        self::assertStringContainsString('Krůtí stehno test', $row->text());
        self::assertStringContainsString('199 Kč', $row->text());

        // 3 recipes with del_flag = 0 (incl. the inactive one), the soft-deleted one is not counted
        $recipesLink = $row->filter('a[href="/admin/produkty/3001/recepty"]');
        self::assertCount(1, $recipesLink);
        self::assertStringContainsString('Recepty (3)', $recipesLink->text());

        $noRecipesLink = $crawler->filter('#item-3002 a[href="/admin/produkty/3002/recepty"]');
        self::assertCount(1, $noRecipesLink);
        self::assertStringContainsString('Recepty (0)', $noRecipesLink->text());

        self::assertCount(1, $row->filter('a[href="/admin/produkty/3001"]'), 'Edit link');
        self::assertCount(1, $crawler->filter('a[href="/admin/produkty/pridat"]'), 'Add link');

        // Meat / products: toggle + delete + move
        $this->gridActionForm($crawler, 'product', 3001, 'deactivate');
        $this->assertNoGridAction($crawler, 'product', 3001, 'activate');
        $this->gridActionForm($crawler, 'product', 3001, 'delete');
        $this->gridActionForm($crawler, 'product', 3001, 'move-down');
        $this->gridActionForm($crawler, 'product', 3002, 'activate');
        $this->assertNoGridAction($crawler, 'product', 3002, 'deactivate');

        // Inactive rows exist (3002, 102) → activate all enabled
        $activateAll = $crawler->filter('form[action="/admin/record/product/activate-all"]');
        self::assertCount(1, $activateAll);
        self::assertCount(0, $activateAll->filter('button[disabled], input[type="submit"][disabled]'));
    }

    #[DataProvider('provideTurkeyProducts')]
    public function testTurkeyProductsHaveNoToggleAndDelete(int $productId): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#item-' . $productId), 'Turkey product is listed');

        $this->assertNoGridAction($crawler, 'product', $productId, 'activate');
        $this->assertNoGridAction($crawler, 'product', $productId, 'deactivate');
        $this->assertNoGridAction($crawler, 'product', $productId, 'delete');

        // Still editable, reorderable and has recipes
        self::assertCount(1, $crawler->filter(sprintf('#item-%1$d a[href="/admin/produkty/%1$d"]', $productId)));
        self::assertCount(1, $crawler->filter(sprintf('#item-%1$d a[href="/admin/produkty/%1$d/recepty"]', $productId)));
        $this->gridActionForm($crawler, 'product', $productId, 'move-up');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideTurkeyProducts(): iterable
    {
        yield 'type 3 turkey' => [1];
        yield 'type 3 half turkey' => [2];
        yield 'type 4 tom' => [47];
        yield 'type 4 half tom' => [48];
    }

    public function testDeletedProductsAreNotListed(): void
    {
        $this->insert('product', [
            'id' => 3050, 'name' => 'Smazaný produkt', 'type' => 1, 'unit' => 'kg', 'price' => 10, 'order' => 3050, 'del_flag' => 1,
        ]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#item-3050'));
        self::assertCount(1, $crawler->filter('#item-3001'));
    }
}
