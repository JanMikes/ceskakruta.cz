<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Grid;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Generic grid actions (PLAN §2 [A] [D] [X], §3.3) on every whitelisted table, driven through the list pages.
 */
final class GridActionsTest extends AdminWebTestCase
{
    /**
     * table => [list url, id of a base fixture row listed there]
     *
     * @return \Generator<string, array{string, string, int}>
     */
    public static function toggleableTables(): \Generator
    {
        yield 'actuality' => ['actuality', '/admin/aktuality', 1];
        yield 'calendar_dead' => ['calendar_dead', '/admin/rozkrmena-krutata', 1];
        yield 'calendar_cold' => ['calendar_cold', '/admin/chlazene-kruty', 1];
        yield 'calendar_alive' => ['calendar_alive', '/admin/zive-kruty', 1];
        yield 'product' => ['product', '/admin/produkty', 100];
        yield 'recipe' => ['recipe', '/admin/produkty/100/recepty', 1];
        yield 'sortiment' => ['sortiment', '/admin/sortiment/1', 3];
        yield 'vendor' => ['vendor', '/admin/prodejci', 1];
        yield 'place' => ['place', '/admin/prodejny', 7];
    }

    /**
     * @return \Generator<string, array{string, string, int}>
     */
    public static function deletableTables(): \Generator
    {
        yield 'actuality' => ['actuality', '/admin/aktuality', 1];
        yield 'calendar_dead' => ['calendar_dead', '/admin/rozkrmena-krutata', 1];
        yield 'calendar_cold' => ['calendar_cold', '/admin/chlazene-kruty', 1];
        yield 'calendar_alive' => ['calendar_alive', '/admin/zive-kruty', 1];
        yield 'product' => ['product', '/admin/produkty', 100];
        yield 'recipe' => ['recipe', '/admin/produkty/100/recepty', 1];
        yield 'sortiment' => ['sortiment', '/admin/sortiment/1', 3];
        yield 'vendor' => ['vendor', '/admin/prodejci', 1];
        yield 'newsletter' => ['newsletter', '/admin/emaily', 1];
        yield 'place_closed_day' => ['place_closed_day', '/admin/prodejny', 1];
    }

    #[DataProvider('toggleableTables')]
    public function testDeactivate(string $table, string $listUrl, int $id): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();

        $this->client->submit($this->gridActionForm($crawler, $table, $id, 'deactivate'));

        $this->assertRedirectsTo($listUrl, $id);
        $row = $this->fetchRow($table, $id);
        self::assertSame(0, $this->fetchColumnInt($table, $id, 'active_flag'));
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);
        self::assertSame('admin', $row['upd_process']);

        $this->client->followRedirect();
        $this->assertFlash('Záznam úspěšně deaktivován');
    }

    #[DataProvider('toggleableTables')]
    public function testActivate(string $table, string $listUrl, int $id): void
    {
        $this->connection()->update('`' . $table . '`', ['active_flag' => 0], ['id' => $id]);
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();

        // Inactive rows stay in the list (can be activated again), only the action changes
        $this->assertNoGridAction($crawler, $table, $id, 'deactivate');
        $this->client->submit($this->gridActionForm($crawler, $table, $id, 'activate'));

        $this->assertRedirectsTo($listUrl, $id);
        self::assertSame(1, $this->fetchColumnInt($table, $id, 'active_flag'));

        $this->client->followRedirect();
        $this->assertFlash('Záznam úspěšně aktivován');
    }

    #[DataProvider('deletableTables')]
    public function testSoftDelete(string $table, string $listUrl, int $id): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();

        $this->client->submit($this->gridActionForm($crawler, $table, $id, 'delete'));

        $anchor = $table === 'place_closed_day' ? '#closed-day-' . $id : '#item-' . $id;
        self::assertResponseRedirects($listUrl . $anchor);
        // Soft delete only — the old app still reads the row
        $row = $this->fetchRow($table, $id);
        self::assertSame(1, $this->fetchColumnInt($table, $id, 'del_flag'));
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam úspěšně smazán');
        self::assertNotContains($id, $this->listedIdsOf($crawler, $table));
    }

    /**
     * Rows whose actions are hidden in the list (PLAN §2.9, §2.11, §2.15) must be refused by the handler as well.
     *
     * @return \Generator<string, array{string, string, int, string}>
     */
    public static function forbiddenActions(): \Generator
    {
        foreach ([1, 2, 41, 42, 47, 48, 49, 50] as $turkeyProductId) {
            yield "product $turkeyProductId delete" => ['product', '/admin/produkty', $turkeyProductId, 'delete'];
            yield "product $turkeyProductId deactivate" => ['product', '/admin/produkty', $turkeyProductId, 'deactivate'];
        }

        yield 'sortiment 1 delete' => ['sortiment', '/admin/sortiment/1', 1, 'delete'];
        yield 'sortiment 2 delete' => ['sortiment', '/admin/sortiment/1', 2, 'delete'];
        yield 'place delete' => ['place', '/admin/prodejny', 7, 'delete'];
    }

    #[DataProvider('forbiddenActions')]
    public function testForbiddenActionIsNotRenderedAndIsRefused(string $table, string $listUrl, int $id, string $action): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();
        self::assertContains($id, $this->listedIdsOf($crawler, $table));

        $this->assertNoGridAction($crawler, $table, $id, $action);

        // Posted anyway, with a valid token taken from another action form of the page
        $token = $this->gridToken($crawler);
        $before = $this->fetchRow($table, $id);

        $this->client->request('POST', sprintf('/admin/record/%s/%d/%s', $table, $id, $action), [
            '_token' => $token,
            '_target' => $listUrl,
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->fetchRow($table, $id));
    }

    public function testContentHasNoGridActions(): void
    {
        // Texty: edit only (PLAN §2.12)
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/texty');
        self::assertResponseIsSuccessful();

        self::assertContains(1, $this->listedIds($crawler));
        self::assertCount(0, $crawler->filter('form[action^="/admin/record/"]'));
    }

    public function testNewsletterEmailsCanOnlyBeDeleted(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/emaily');
        self::assertResponseIsSuccessful();

        $this->gridActionForm($crawler, 'newsletter', 1, 'delete');
        foreach (['activate', 'deactivate', 'move-up', 'move-down'] as $action) {
            $this->assertNoGridAction($crawler, 'newsletter', 1, $action);
        }
    }

    /**
     * Ids of a table that have any grid action form on the page (the places page has two lists).
     *
     * @return list<int>
     */
    private function listedIdsOf(Crawler $crawler, string $table): array
    {
        $ids = $crawler->filter(sprintf('form[action^="/admin/record/%s/"]', $table))
            ->each(static fn (Crawler $form): int => (int) explode('/', (string) $form->attr('action'))[4]);

        return array_values(array_unique($ids));
    }
}
