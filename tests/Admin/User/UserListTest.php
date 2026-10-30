<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\User;

final class UserListTest extends UserTestCase
{
    public function testColumnsOrdersCountAndBannedCount(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::USERS_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Uživatelé');

        // Newest registration first (ins_dt DESC)
        $this->assertListedInOrder($crawler, [
            self::USER_EDIT, self::USER_WHOLESALE, self::USER_PLAIN, self::USER_ADMIN, self::USER_WITH_ORDERS,
            self::WHOLESALE_ID, self::CUSTOMER_ID, self::ADMIN_ID,
        ]);

        $row = $this->userRow($crawler, self::USER_WITH_ORDERS);
        $cells = $this->cellTexts($row);
        self::assertGreaterThanOrEqual(8, count($cells));
        self::assertSame('4001', $cells[0]);
        self::assertSame('04.03.2025 05:06', $cells[1]);
        self::assertStringContainsString('pu-orders@places-users.test', $cells[2]);
        self::assertStringContainsString('PU Objednávající', $cells[3]);
        self::assertSame('3', $cells[4], 'Orders count by user_id.');

        // Banned count = active & non-deleted bans (2 of 4 rows), links to the banned products page
        $banned = $row->filter('a[href="/admin/uzivatele/4001/zakazane-produkty"]');
        self::assertCount(1, $banned);
        self::assertSame('2 zakázaných', self::normalize($banned->text()));

        // Edit link
        self::assertCount(1, $row->filter('a[href="/admin/uzivatele/4001"]'));

        // Users without orders / bans
        $plain = $this->userRow($crawler, self::USER_PLAIN);
        self::assertSame('0', $this->cellTexts($plain)[4]);
        self::assertSame('0 zakázaných', self::normalize(self::single($plain->filter('a[href="/admin/uzivatele/4003/zakazane-produkty"]'), 'banned link')->text()));
        self::assertSame('1 zakázaných', self::normalize(self::single($this->userRow($crawler, self::USER_ADMIN)->filter('a[href="/admin/uzivatele/4002/zakazane-produkty"]'), 'banned link')->text()));
    }

    public function testWholesaleColumn(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::USERS_URL);
        self::assertResponseIsSuccessful();

        $plain = $this->userRow($crawler, self::USER_PLAIN);
        self::assertStringContainsString('Nepovolen', $plain->text());
        $allowForm = $plain->filter('form[method="post"][action="/admin/uzivatele/4003/velkoobchod"]');
        self::assertCount(1, $allowForm);
        self::assertSame('1', self::single($allowForm->filter('[name="allow"]'), 'allow field')->attr('value'));
        self::assertStringContainsString('Povolit', $allowForm->text());

        $wholesale = $this->userRow($crawler, self::USER_WHOLESALE);
        self::assertStringContainsString('Povolen', $wholesale->text());
        self::assertStringNotContainsString('Nepovolen', $wholesale->text());
        $cancelForm = $wholesale->filter('form[method="post"][action="/admin/uzivatele/4004/velkoobchod"]');
        self::assertCount(1, $cancelForm);
        self::assertSame('0', self::single($cancelForm->filter('[name="allow"]'), 'allow field')->attr('value'));
        self::assertStringContainsString('Zakázat', $cancelForm->text());
    }

    public function testAdminBadge(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::USERS_URL);
        self::assertResponseIsSuccessful();

        foreach ([self::USER_ADMIN, self::ADMIN_ID] as $adminId) {
            $badge = $this->userRow($crawler, $adminId)->filter('.badge');
            self::assertGreaterThan(0, $badge->count(), sprintf('User #%d has an admin badge.', $adminId));
            self::assertStringContainsString('Administrátor', $badge->text());
        }

        foreach ([self::USER_PLAIN, self::USER_WHOLESALE, self::CUSTOMER_ID] as $userId) {
            self::assertStringNotContainsString('Administrátor', $this->userRow($crawler, $userId)->text());
        }
    }
}
