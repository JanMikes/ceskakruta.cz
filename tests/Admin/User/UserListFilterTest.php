<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\User;

/**
 * User list filter = plain GET params `search` (email, name, phone, company name LIKE), `wholesale` (1/0), `page` (50 per page).
 */
final class UserListFilterTest extends UserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }

    public function testSearchByEmailNamePhoneAndCompany(): void
    {
        $crawler = $this->client->request('GET', self::USERS_URL);
        $form = self::single($crawler->filter('form#admin-user-filter'), 'filter form')->form();
        self::assertSame('GET', $form->getMethod());
        $form['search'] = 'pu-plain@';
        $crawler = $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSame([self::USER_PLAIN], $this->listedIds($crawler));
        self::assertSame('pu-plain@', $crawler->filter('input[name="search"]')->attr('value'));

        foreach (['PU Objednávající' => self::USER_WITH_ORDERS, '+420600000005' => self::USER_EDIT, 'PU Firma' => self::USER_WHOLESALE] as $search => $userId) {
            $crawler = $this->client->request('GET', self::USERS_URL, ['search' => $search]);
            self::assertSame([$userId], $this->listedIds($crawler), sprintf('Search "%s".', $search));
        }

        $crawler = $this->client->request('GET', self::USERS_URL, ['search' => 'nobody-like-this']);
        self::assertSame([], $this->listedIds($crawler));
        self::assertStringContainsString('Filtru neodpovídá žádný uživatel.', $crawler->filter('body')->text());
    }

    public function testFilterByWholesale(): void
    {
        $crawler = $this->client->request('GET', self::USERS_URL, ['wholesale' => '1']);
        self::assertSame([self::USER_WHOLESALE, self::WHOLESALE_ID], $this->listedIds($crawler));
        self::assertSame('1', $crawler->filter('select[name="wholesale"] option[selected]')->attr('value'));

        $notWholesale = $this->listedIds($this->client->request('GET', self::USERS_URL, ['wholesale' => '0']));
        self::assertContains(self::USER_PLAIN, $notWholesale);
        self::assertNotContains(self::USER_WHOLESALE, $notWholesale);
        self::assertNotContains(self::WHOLESALE_ID, $notWholesale);

        // Unknown value = no filter
        self::assertContains(self::USER_WHOLESALE, $this->listedIds($this->client->request('GET', self::USERS_URL, ['wholesale' => 'x'])));
    }

    public function testPaginationKeepsFilter(): void
    {
        // 55 extra users registered newer than all fixtures → 60 matching "pu-paging" + other users
        for ($i = 1; $i <= 55; $i++) {
            $this->insert('user', [
                'name' => sprintf('Stránkování %02d', $i),
                'email' => sprintf('pu-paging-%02d@example.test', $i),
                'ins_dt' => sprintf('2026-01-01 00:00:%02d', $i),
                'company_invoicing' => 0,
                'is_admin' => 0,
                'allow_wholesale' => 0,
                'is_ceskakruta' => 0,
                'active_flag' => 1,
                'del_flag' => 0,
            ]);
        }

        $crawler = $this->client->request('GET', self::USERS_URL, ['search' => 'pu-paging']);
        self::assertResponseIsSuccessful();
        self::assertCount(50, $this->listedIds($crawler));
        self::assertStringContainsString('Celkem 55', $crawler->filter('body')->text());
        // Newest first
        self::assertStringContainsString('pu-paging-55@example.test', $crawler->filter('tbody tr')->first()->text());

        $links = $crawler->filter('nav[aria-label="Stránkování"] a[href*="page=2"]');
        self::assertGreaterThan(0, $links->count(), 'Link to page 2 not found.');
        self::assertStringContainsString('search=pu-paging', (string) $links->first()->attr('href'));

        $crawler = $this->client->click($links->first()->link());
        self::assertResponseIsSuccessful();
        self::assertCount(5, $this->listedIds($crawler));
        self::assertStringContainsString('pu-paging-01@example.test', $crawler->filter('body')->text());
    }

    public function testWholesaleToggleReturnsToTheSameListPage(): void
    {
        $crawler = $this->client->request('GET', self::USERS_URL, ['search' => 'pu-plain']);
        $form = self::single($crawler->filter('form[action="/admin/uzivatele/4003/velkoobchod"]'), 'wholesale form')->form();
        $this->client->submit($form);

        $this->assertRedirectsTo(self::USERS_URL . '?search=pu-plain', self::USER_PLAIN);
    }

    public function testWholesaleToggleIgnoresForeignTarget(): void
    {
        $crawler = $this->client->request('GET', self::USERS_URL);
        $form = self::single($crawler->filter('form[action="/admin/uzivatele/4003/velkoobchod"]'), 'wholesale form')->form();
        $form['_target'] = 'https://evil.example/admin/uzivatele';
        $this->client->submit($form);

        $this->assertRedirectsTo(self::USERS_URL, self::USER_PLAIN);
    }
}
