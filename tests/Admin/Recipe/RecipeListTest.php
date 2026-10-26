<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Recipe;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

/**
 * PLAN §2.10 list: per existing product (else redirect to products), sorted `name ASC`, Název + Obrázek,
 * [A]/[D], Edit, [X], [AA] (scoped to the product), back button to products.
 */
final class RecipeListTest extends AdminWebTestCase
{
    public function testUnknownProductRedirects(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/produkty/3999/recepty');

        $this->assertRedirectsTo('/admin/produkty');
    }

    public function testDeletedProductRedirects(): void
    {
        $this->insert('product', ['id' => 3060, 'name' => 'Smazaný', 'type' => 1, 'unit' => 'kg', 'del_flag' => 1]);

        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/produkty/3060/recepty');

        $this->assertRedirectsTo('/admin/produkty');
    }

    public function testListSortedByName(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Krůtí stehno test');

        // Dušené, Grilované (inactive, still listed), Pečené; soft-deleted 3003 hidden; recipe 1 belongs to product 100
        self::assertSame([3002, 3004, 3001], $this->listedIds($crawler));

        self::assertCount(1, $crawler->filter('#item-3001'));
        self::assertStringContainsString('Pečené stehno', $crawler->filter('#item-3001')->text());
        self::assertCount(1, $crawler->filter('#item-3001 a[href="/admin/produkty/3001/recepty/3001"]'), 'Edit link');
        self::assertCount(1, $crawler->filter('a[href="/admin/produkty/3001/recepty/pridat"]'), 'Add link');
        self::assertGreaterThan(0, $crawler->filterXPath('//a[@href="/admin/produkty" and not(ancestor::nav)]')->count(), 'Back button to products');
    }

    public function testRowActionsAndScopedActivateAll(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty');

        self::assertResponseIsSuccessful();

        $this->gridActionForm($crawler, 'recipe', 3001, 'deactivate');
        $this->gridActionForm($crawler, 'recipe', 3001, 'delete');
        $this->gridActionForm($crawler, 'recipe', 3004, 'activate');
        $this->assertNoGridAction($crawler, 'recipe', 3004, 'deactivate');
        // Recipes are sorted by name, not orderable
        $this->assertNoGridAction($crawler, 'recipe', 3002, 'move-up');
        $this->assertNoGridAction($crawler, 'recipe', 3002, 'move-down');

        $activateAll = $crawler->filter('form[action="/admin/record/recipe/activate-all"]');
        self::assertCount(1, $activateAll);
        self::assertCount(1, $activateAll->filter('input[name="scope"]'));
        self::assertSame('3001', $activateAll->filter('input[name="scope"]')->attr('value'));
        self::assertCount(0, $activateAll->filter('button[disabled], input[type="submit"][disabled]'));
    }

    public function testActivateAllDisabledWhenNoInactiveRecipe(): void
    {
        // Own product with one active recipe; inactive recipes of other products (3004) must not matter
        $this->insert('product', ['id' => 3070, 'name' => 'Produkt s aktivním receptem', 'type' => 1, 'unit' => 'kg', 'price' => 10]);
        $recipeId = $this->insert('recipe', ['product_id' => 3070, 'name' => 'Aktivní recept', 'active_flag' => 1]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3070/recepty');

        self::assertResponseIsSuccessful();
        self::assertSame([$recipeId], $this->listedIds($crawler));

        $activateAll = $crawler->filter('form[action="/admin/record/recipe/activate-all"]');
        self::assertCount(1, $activateAll);
        self::assertCount(1, $activateAll->filter('button[disabled], input[type="submit"][disabled]'));
    }

    public function testShowsImage(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/3001/recepty');

        self::assertResponseIsSuccessful();

        $image = $crawler->filter('#item-3001 img');
        self::assertCount(1, $image);
        // Served locally from the uploads root, legacy relative layout `photo.path/photo.filename` (PLAN §3.9)
        self::assertStringEndsWith('/upload/recipes/5f0c1a2b3c4d5-3001.jpg', (string) $image->attr('src'));
        self::assertStringNotContainsString('old.ceskakruta.cz', (string) $image->attr('src'));

        self::assertCount(0, $crawler->filter('#item-3002 img'), 'Recipe without photo has no image');
    }
}
