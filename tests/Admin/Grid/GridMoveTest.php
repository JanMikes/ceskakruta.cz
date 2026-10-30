<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Grid;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * [M] Move up / down — port of the old `Entity::changeRowOrder()` (PLAN §2):
 * lists are sorted `order DESC`; empty order is initialised to id*10 first; the row swaps `order` with the nearest
 * non-deleted row in the direction (up = nearest order >= own, down = nearest order <= own); on equal values ±1.
 * Order values >= 100000 are reserved for these tests (other fixtures stay below), so only own rows are neighbours.
 */
final class GridMoveTest extends AdminWebTestCase
{
    /**
     * @return \Generator<string, array{string, string, array<string, scalar|null>}>
     */
    public static function orderableTables(): \Generator
    {
        yield 'vendor' => ['vendor', '/admin/prodejci', ['name' => 'Prodejce']];
        yield 'sortiment_category' => ['sortiment_category', '/admin/sortiment', ['name' => 'Kategorie']];
        yield 'product' => ['product', '/admin/produkty', ['name' => 'Produkt', 'type' => 1, 'unit' => 'kg', 'price' => 100, 'packages_size' => '[]']];
        yield 'place' => ['place', '/admin/prodejny', ['name' => 'Prodejna', 'title' => 'Prodejna', 'force_delivery_address' => 0, 'hide_in_admin' => 0]];
    }

    /**
     * @param array<string, scalar|null> $columns
     */
    #[DataProvider('orderableTables')]
    public function testMoveUp(string $table, string $listUrl, array $columns): void
    {
        $this->insert($table, ['id' => 6101, 'order' => 100030] + $columns);
        $this->insert($table, ['id' => 6102, 'order' => 100020] + $columns);
        $this->insert($table, ['id' => 6103, 'order' => 100010] + $columns);

        $this->move($table, $listUrl, 6103, 'move-up');

        $this->assertRedirectsTo($listUrl, 6103);
        self::assertSame([6101 => 100030, 6102 => 100010, 6103 => 100020], $this->orders($table, [6101, 6102, 6103]));

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Pořadí bylo úspěšně změněno!');
        $this->assertListedInOrder($crawler, [6101, 6103, 6102]);
    }

    /**
     * @param array<string, scalar|null> $columns
     */
    #[DataProvider('orderableTables')]
    public function testMoveDown(string $table, string $listUrl, array $columns): void
    {
        $this->insert($table, ['id' => 6101, 'order' => 100030] + $columns);
        $this->insert($table, ['id' => 6102, 'order' => 100020] + $columns);
        $this->insert($table, ['id' => 6103, 'order' => 100010] + $columns);

        $this->move($table, $listUrl, 6101, 'move-down');

        $this->assertRedirectsTo($listUrl, 6101);
        self::assertSame([6101 => 100020, 6102 => 100030, 6103 => 100010], $this->orders($table, [6101, 6102, 6103]));
    }

    public function testInitialisesEmptyOrder(): void
    {
        $this->insert('vendor', ['id' => 6101, 'order' => 100010, 'name' => 'A']);
        $this->insert('vendor', ['id' => 6104, 'order' => null, 'name' => 'Bez pořadí']);

        $this->move('vendor', '/admin/prodejci', 6104, 'move-up');

        // 6104 first gets 6104*10 = 61040, then swaps with the nearest higher row
        self::assertSame([6101 => 61040, 6104 => 100010], $this->orders('vendor', [6101, 6104]));
    }

    public function testEqualOrderMovesUpByOne(): void
    {
        $this->insert('vendor', ['id' => 6101, 'order' => 100050, 'name' => 'A']);
        $this->insert('vendor', ['id' => 6102, 'order' => 100050, 'name' => 'B']);

        $this->postMove('vendor', '/admin/prodejci', 6102, 'move-up');

        self::assertSame([6101 => 100050, 6102 => 100051], $this->orders('vendor', [6101, 6102]));
    }

    public function testEqualOrderMovesDownByOne(): void
    {
        $this->insert('vendor', ['id' => 6101, 'order' => 100050, 'name' => 'A']);
        $this->insert('vendor', ['id' => 6102, 'order' => 100050, 'name' => 'B']);

        $this->postMove('vendor', '/admin/prodejci', 6101, 'move-down');

        self::assertSame([6101 => 100049, 6102 => 100050], $this->orders('vendor', [6101, 6102]));
    }

    public function testSkipsDeletedRows(): void
    {
        $this->insert('vendor', ['id' => 6101, 'order' => 100030, 'name' => 'A']);
        $this->insert('vendor', ['id' => 6102, 'order' => 100020, 'name' => 'Smazaný', 'del_flag' => 1]);
        $this->insert('vendor', ['id' => 6103, 'order' => 100010, 'name' => 'C']);

        $this->move('vendor', '/admin/prodejci', 6103, 'move-up');

        self::assertSame([6101 => 100010, 6102 => 100020, 6103 => 100030], $this->orders('vendor', [6101, 6102, 6103]));
    }

    public function testRespectsScope(): void
    {
        $this->insert('sortiment_category', ['id' => 6000, 'order' => 5, 'name' => 'Testovací kategorie']);
        $this->insert('sortiment', ['id' => 6201, 'category_id' => 6000, 'order' => 100040, 'name' => 'S1']);
        $this->insert('sortiment', ['id' => 6202, 'category_id' => 6000, 'order' => 100030, 'name' => 'S2']);
        $this->insert('sortiment', ['id' => 6203, 'category_id' => 6000, 'order' => 100020, 'name' => 'S3']);
        $this->insert('sortiment', ['id' => 6204, 'category_id' => 6000, 'order' => 100010, 'name' => 'S4']);
        // Other category, numerically the nearest — must be ignored
        $this->insert('sortiment', ['id' => 6205, 'category_id' => 3, 'order' => 100015, 'name' => 'Jiná kategorie']);

        $this->move('sortiment', '/admin/sortiment/6000', 6204, 'move-up');

        $this->assertRedirectsTo('/admin/sortiment/6000', 6204);
        self::assertSame(
            [6203 => 100010, 6204 => 100020, 6205 => 100015],
            $this->orders('sortiment', [6203, 6204, 6205]),
        );
    }

    public function testNoNeighbourIsNoop(): void
    {
        $this->insert('vendor', ['id' => 6101, 'order' => 100030, 'name' => 'Nejvyšší']);
        $this->insert('vendor', ['id' => 6102, 'order' => 100020, 'name' => 'B']);

        $this->postMove('vendor', '/admin/prodejci', 6101, 'move-up');

        self::assertResponseRedirects();
        self::assertSame([6101 => 100030, 6102 => 100020], $this->orders('vendor', [6101, 6102]));
    }

    public function testFirstRowHasNoUpArrowAndLastRowNoDownArrow(): void
    {
        $this->insert('vendor', ['id' => 6101, 'order' => 100030, 'name' => 'První']);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/prodejci');
        self::assertResponseIsSuccessful();

        $ids = $this->listedIds($crawler);
        self::assertSame(6101, $ids[0]);
        $last = $ids[count($ids) - 1];

        $this->assertNoGridAction($crawler, 'vendor', 6101, 'move-up');
        $this->gridActionForm($crawler, 'vendor', 6101, 'move-down');
        $this->assertNoGridAction($crawler, 'vendor', $last, 'move-down');
        $this->gridActionForm($crawler, 'vendor', $last, 'move-up');
    }

    public function testNotOrderableTableIsRefused(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/aktuality');
        self::assertResponseIsSuccessful();
        $token = $this->gridToken($crawler);

        $this->client->request('POST', '/admin/record/actuality/1/move-up', ['_token' => $token, '_target' => '/admin/aktuality']);

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * Clicks the arrow in the list (the row must have it).
     */
    private function move(string $table, string $listUrl, int $id, string $direction): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();

        $this->client->submit($this->gridActionForm($crawler, $table, $id, $direction));
    }

    /**
     * Posts the action directly (for rows whose arrow is hidden), with a valid token from the list page.
     */
    private function postMove(string $table, string $listUrl, int $id, string $direction): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', $listUrl);
        self::assertResponseIsSuccessful();
        $token = $this->gridToken($crawler);

        $this->client->request('POST', sprintf('/admin/record/%s/%d/%s', $table, $id, $direction), [
            '_token' => $token,
            '_target' => $listUrl,
        ]);
    }

    /**
     * @param list<int> $ids
     * @return array<int, null|int>
     */
    private function orders(string $table, array $ids): array
    {
        $orders = [];

        foreach ($ids as $id) {
            $orders[$id] = $this->fetchColumnInt($table, $id, 'order');
        }

        return $orders;
    }
}
