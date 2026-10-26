<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\User;

use Symfony\Component\DomCrawler\Crawler;

final class SetUserBannedProductsTest extends UserTestCase
{
    private const URL = '/admin/uzivatele/4001/zakazane-produkty';
    private const FIELD = 'banned_products[products][]';

    public function testShowsActiveProductsPrechecked(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::URL);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'pu-orders@places-users.test');

        $checkboxes = $this->checkboxes($crawler);
        $ids = array_map(static fn (array $checkbox): int => $checkbox['id'], $checkboxes);

        // All active products, sorted by name; inactive product 102 missing
        self::assertNotContains(102, $ids);
        $own = array_values(array_filter($ids, static fn (int $id): bool => in_array($id, [47, 1, 100, 101, 41, 2], true)));
        self::assertSame([47, 1, 100, 101, 41, 2], $own);

        // Pre-checked = active, non-deleted bans (100, 1); inactive (41) and deleted (42) bans are not checked
        $checked = array_values(array_map(
            static fn (array $checkbox): int => $checkbox['id'],
            array_filter($checkboxes, static fn (array $checkbox): bool => $checkbox['checked']),
        ));
        sort($checked);
        self::assertSame([1, 100], $checked);

        // Product name is the label
        $label = $crawler->filter(sprintf('label[for="%s"]', $this->checkboxDomId($crawler, 100)));
        self::assertSame('Krůtí prsa', trim(self::single($label, 'label')->text()));
    }

    public function testReplacesBans(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        $values = $form->getPhpValues();
        self::assertIsArray($values['banned_products'] ?? null);
        $values['banned_products']['products'] = ['101', '41'];
        $this->submitValues($form, $values);

        $this->assertRedirectsTo(self::USERS_URL, self::USER_WITH_ORDERS);

        // Hard delete + one row per checked product (previous inactive/deleted rows are gone too)
        self::assertSame([41, 101], $this->bannedProductIds(self::USER_WITH_ORDERS));
        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM user_banned_product WHERE id IN (4001, 4002, 4003, 4004)'));

        $rows = $this->connection()->fetchAllAssociative('SELECT * FROM user_banned_product WHERE user_id = :id', ['id' => self::USER_WITH_ORDERS]);
        foreach ($rows as $row) {
            self::assertSame(1, self::toInt($row['active_flag']));
            self::assertSame(0, self::toInt($row['del_flag']));
            self::assertSame('admin', $row['ins_process']);
            self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);
        }

        // Other users' bans untouched
        self::assertSame([100], $this->bannedProductIds(self::USER_ADMIN));

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertSame('2 zakázaných', trim(self::single($this->userRow($crawler, self::USER_WITH_ORDERS)->filter('a[href="' . self::URL . '"]'), 'banned link')->text()));
    }

    public function testClearAll(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        $values = $form->getPhpValues();
        unset($values['banned_products']['products']);
        $this->submitValues($form, $values);

        self::assertResponseRedirects();
        self::assertSame([], $this->bannedProductIds(self::USER_WITH_ORDERS));
        self::assertSame([100], $this->bannedProductIds(self::USER_ADMIN));

        $crawler = $this->client->followRedirect();
        self::assertSame('0 zakázaných', trim(self::single($this->userRow($crawler, self::USER_WITH_ORDERS)->filter('a[href="' . self::URL . '"]'), 'banned link')->text()));
    }

    /**
     * @return list<array{id: int, checked: bool}>
     */
    private function checkboxes(Crawler $crawler): array
    {
        $inputs = $crawler->filter(sprintf('input[type="checkbox"][name="%s"]', self::FIELD));
        self::assertGreaterThan(0, $inputs->count(), 'Banned products checkboxes not found.');

        return array_values($inputs->each(static fn (Crawler $input): array => [
            'id' => (int) $input->attr('value'),
            'checked' => $input->attr('checked') !== null,
        ]));
    }

    private function checkboxDomId(Crawler $crawler, int $productId): string
    {
        $input = $crawler->filter(sprintf('input[type="checkbox"][name="%s"][value="%d"]', self::FIELD, $productId));
        self::assertCount(1, $input);

        return (string) $input->attr('id');
    }
}
