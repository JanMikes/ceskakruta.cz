<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Support;

use CeskaKruta\Web\Services\Security\UserProvider;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

/**
 * Base for functional admin tests, see docs/admin/PLAN.md §4.6 for the UI contract these helpers rely on.
 */
abstract class AdminWebTestCase extends WebTestCase
{
    // Fixture users (tests/database/fixtures.sql)
    protected const ADMIN_ID = 1;
    protected const ADMIN_EMAIL = 'admin@example.test';
    protected const ADMIN_PASSWORD = 'admin-password';
    protected const CUSTOMER_ID = 2;
    protected const CUSTOMER_EMAIL = 'customer@example.test';
    protected const CUSTOMER_PASSWORD = 'customer-password';
    protected const WHOLESALE_ID = 3;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        HttpMock::reset();
        $this->client = self::createClient();
    }

    protected function loginAsAdmin(): void
    {
        $this->loginAs(self::ADMIN_EMAIL);
    }

    protected function loginAsCustomer(): void
    {
        $this->loginAs(self::CUSTOMER_EMAIL);
    }

    protected function loginAs(string $email): void
    {
        $user = self::getContainer()->get(UserProvider::class)->loadUserByIdentifier($email);

        $this->client->loginUser($user, 'admin');
    }

    protected function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }

    /**
     * Inserts a row directly (arrange step), `ins_dt` defaults to now. Returns the id.
     *
     * @param array<string, scalar|null> $row
     */
    protected function insert(string $table, array $row): int
    {
        $row += ['ins_dt' => date('Y-m-d H:i:s')];

        $quoted = [];
        foreach ($row as $column => $value) {
            $quoted['`' . $column . '`'] = is_bool($value) ? (int) $value : $value;
        }

        $this->connection()->insert('`' . $table . '`', $quoted);

        return (int) $this->connection()->lastInsertId();
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchRow(string $table, int $id): array
    {
        $row = $this->connection()->fetchAssociative(sprintf('SELECT * FROM `%s` WHERE id = :id', $table), ['id' => $id]);
        self::assertIsArray($row, sprintf('Row %s#%d not found.', $table, $id));

        return $row;
    }

    /**
     * Single column of a row as int (null stays null), e.g. `active_flag`, `order`.
     */
    protected function fetchColumnInt(string $table, int $id, string $column): null|int
    {
        $value = $this->fetchRow($table, $id)[$column] ?? null;

        if ($value === null) {
            return null;
        }

        self::assertIsNumeric($value);

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function fetchInt(string $sql, array $params = []): int
    {
        $value = $this->connection()->fetchOne($sql, $params);
        self::assertIsNumeric($value);

        return (int) $value;
    }

    protected function lastInsertedId(string $table): int
    {
        return $this->fetchInt(sprintf('SELECT MAX(id) FROM `%s`', $table));
    }

    /**
     * Flash messages are rendered as Bootstrap alerts: `.alert-success` / `.alert-danger` (PLAN §4.6).
     */
    protected function assertFlash(string $message, string $type = 'success'): void
    {
        self::assertSelectorTextContains('.alert-' . $type, $message);
    }

    /**
     * Asserts a redirect to the path (without host), optionally with the `#item-{id}` anchor.
     */
    protected function assertRedirectsTo(string $path, null|int $itemAnchor = null): void
    {
        self::assertResponseRedirects();

        $location = (string) $this->client->getResponse()->headers->get('Location');
        $location = (string) preg_replace('~^https?://[^/]+~', '', $location);

        self::assertSame($path . ($itemAnchor !== null ? '#item-' . $itemAnchor : ''), $location);
    }

    /**
     * Ids of list rows in DOM order (rows are rendered with `id="item-{id}"`).
     *
     * @return list<int>
     */
    protected function listedIds(Crawler $crawler): array
    {
        return array_values(array_map(
            static fn (string $id): int => (int) substr($id, 5),
            $crawler->filter('[id^="item-"]')->each(static fn (Crawler $node): string => (string) $node->attr('id')),
        ));
    }

    /**
     * Asserts that `$expectedOrder` ids appear in the list in this relative order (other rows may be in between).
     *
     * @param list<int> $expectedOrder
     */
    protected function assertListedInOrder(Crawler $crawler, array $expectedOrder): void
    {
        $listed = array_values(array_filter($this->listedIds($crawler), static fn (int $id): bool => in_array($id, $expectedOrder, true)));

        self::assertSame($expectedOrder, $listed);
    }

    /**
     * Generic grid action form of a row: action one of activate|deactivate|delete|move-up|move-down (PLAN §3.3, §4.6).
     */
    protected function gridActionForm(Crawler $crawler, string $table, int $id, string $action): Form
    {
        $forms = $crawler->filter(sprintf('form[action="/admin/record/%s/%d/%s"]', $table, $id, $action));
        self::assertCount(1, $forms, sprintf('Grid action "%s" for %s#%d not found.', $action, $table, $id));

        return $forms->form();
    }

    /**
     * CSRF token (`admin-action`) of the first grid action form on the page, for posting forbidden actions directly.
     */
    protected function gridToken(Crawler $crawler): string
    {
        $tokens = $crawler->filter('form[action^="/admin/record/"] input[name="_token"]');
        self::assertGreaterThan(0, $tokens->count(), 'No grid action form with a CSRF token on the page.');

        return (string) $tokens->first()->attr('value');
    }

    protected function assertNoGridAction(Crawler $crawler, string $table, int $id, string $action): void
    {
        self::assertCount(0, $crawler->filter(sprintf('form[action="/admin/record/%s/%d/%s"]', $table, $id, $action)));
    }
}
