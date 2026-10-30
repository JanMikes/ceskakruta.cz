<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Grid;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * [AA] Activate all (PLAN §2, §3.3). New: scoped to the current list (old bug activated recipes/sortiment of all scopes).
 */
final class GridActivateAllTest extends AdminWebTestCase
{
    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function tables(): \Generator
    {
        yield 'actuality' => ['actuality', '/admin/aktuality'];
        yield 'calendar_dead' => ['calendar_dead', '/admin/rozkrmena-krutata'];
        yield 'calendar_cold' => ['calendar_cold', '/admin/chlazene-kruty'];
        yield 'product' => ['product', '/admin/produkty'];
        yield 'vendor' => ['vendor', '/admin/prodejci'];
        yield 'place' => ['place', '/admin/prodejny'];
    }

    #[DataProvider('tables')]
    public function testActivatesAllInactive(string $table, string $listUrl): void
    {
        $inactive = $this->insert($table, ['id' => 6001, 'active_flag' => 0] + $this->requiredColumns($table));
        $deleted = $this->insert($table, ['id' => 6002, 'active_flag' => 0, 'del_flag' => 1] + $this->requiredColumns($table));

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();

        $this->client->submit($this->activateAllForm($crawler, $table));

        self::assertResponseRedirects($listUrl);
        self::assertSame(1, $this->fetchColumnInt($table, $inactive, 'active_flag'));
        self::assertSame(0, $this->fetchInt(sprintf('SELECT COUNT(*) FROM `%s` WHERE active_flag = 0 AND del_flag = 0', $table)));
        // Deleted rows stay untouched
        self::assertSame(0, $this->fetchColumnInt($table, $deleted, 'active_flag'));

        $this->client->followRedirect();
        $this->assertFlash('Záznamy úspěšně aktivovány');
    }

    #[DataProvider('tables')]
    public function testButtonDisabledWhenNothingIsInactive(string $table, string $listUrl): void
    {
        $this->connection()->update('`' . $table . '`', ['active_flag' => 1], ['active_flag' => 0]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();

        self::assertCount(1, $crawler->filter(sprintf('form[action="/admin/record/%s/activate-all"] button[disabled]', $table)));
    }

    public function testRecipesActivateAllRespectsProductScope(): void
    {
        $thisProduct = $this->insert('recipe', ['id' => 6011, 'product_id' => 100, 'name' => 'Recept A', 'active_flag' => 0]);
        $otherProduct = $this->insert('recipe', ['id' => 6012, 'product_id' => 101, 'name' => 'Recept B', 'active_flag' => 0]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/produkty/100/recepty');
        self::assertResponseIsSuccessful();

        $form = $this->activateAllForm($crawler, 'recipe');
        self::assertSame('100', $form->getValues()['scope']);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/produkty/100/recepty');
        self::assertSame(1, $this->fetchColumnInt('recipe', $thisProduct, 'active_flag'));
        self::assertSame(0, $this->fetchColumnInt('recipe', $otherProduct, 'active_flag'));
    }

    public function testSortimentActivateAllRespectsCategoryScope(): void
    {
        $thisCategory = $this->insert('sortiment', ['id' => 6021, 'category_id' => 2, 'name' => 'Olej A', 'active_flag' => 0]);
        $otherCategory = $this->insert('sortiment', ['id' => 6022, 'category_id' => 3, 'name' => 'Koření B', 'active_flag' => 0]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/sortiment/2');
        self::assertResponseIsSuccessful();

        $form = $this->activateAllForm($crawler, 'sortiment');
        self::assertSame('2', $form->getValues()['scope']);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/sortiment/2');
        self::assertSame(1, $this->fetchColumnInt('sortiment', $thisCategory, 'active_flag'));
        self::assertSame(0, $this->fetchColumnInt('sortiment', $otherCategory, 'active_flag'));
    }

    public function testRefusesTableWithoutActivateAll(): void
    {
        $this->insert('newsletter', ['id' => 6031, 'email' => 'inactive@grid.test', 'active_flag' => 0]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/emaily');
        self::assertResponseIsSuccessful();
        $token = $this->gridToken($crawler);

        $this->client->request('POST', '/admin/record/newsletter/activate-all', ['_token' => $token, '_target' => '/admin/emaily']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, $this->fetchColumnInt('newsletter', 6031, 'active_flag'));
    }

    private function activateAllForm(Crawler $crawler, string $table): \Symfony\Component\DomCrawler\Form
    {
        $forms = $crawler->filter(sprintf('form[action="/admin/record/%s/activate-all"]', $table));
        self::assertCount(1, $forms, sprintf('Activate all form for "%s" not found.', $table));

        return $forms->form();
    }

    /**
     * Extra NOT NULL / meaningful columns per table (everything else has defaults in the production schema).
     *
     * @return array<string, scalar|null>
     */
    private function requiredColumns(string $table): array
    {
        return match ($table) {
            'product' => ['name' => 'Neaktivní produkt', 'type' => 1, 'unit' => 'kg', 'price' => 100, 'packages_size' => '[]', 'order' => 1],
            'place' => ['name' => 'Neaktivní prodejna', 'title' => 'Neaktivní prodejna', 'force_delivery_address' => 0, 'hide_in_admin' => 0],
            'calendar_dead' => ['week_number' => 20, 'year' => 2024],
            'calendar_cold' => ['product_tp_id' => 1, 'week_number' => 20, 'year' => 2024, 'weight_from' => 5, 'weight_to' => 6],
            'actuality' => ['name' => 'Neaktivní aktualita', 'date' => '2024-01-01'],
            default => ['name' => 'Neaktivní záznam'],
        };
    }
}
