<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\User;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

final class EditUserTest extends UserTestCase
{
    private const EDIT_URL = '/admin/uzivatele/4005';

    public function testEdits(): void
    {
        $passwordBefore = $this->fetchRow('user', self::USER_EDIT)['password'];

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        self::assertSame('pu-edit@places-users.test', $this->fieldValue($form, 'user[email]'));
        self::assertSame('7', $this->fieldValue($form, 'user[preferred_place_id]'));
        self::assertSame('Stará 5', $this->fieldValue($form, 'user[delivery_street]'));
        self::assertCount(0, $crawler->filter('[name^="user[password"]'), 'Password is not editable here.');

        $this->setField($form, 'user[email]', 'pu-edited@places-users.test');
        $this->setField($form, 'user[name]', 'PU Upravené Jméno');
        $this->setField($form, 'user[phone]', '+420699999999');
        $this->setField($form, 'user[preferred_place_id]', '22');
        $this->setField($form, 'user[delivery_street]', 'Nová 10');
        $this->setField($form, 'user[delivery_city]', 'Olomouc');
        $this->setField($form, 'user[delivery_zip]', '77900');
        $this->setField($form, 'user[company_invoicing]', true);
        $this->setField($form, 'user[company_name]', 'PU Nová firma a.s.');
        $this->setField($form, 'user[company_id]', '87654321');
        $this->setField($form, 'user[company_vat_id]', 'CZ87654321');
        $this->setField($form, 'user[invoicing_street]', 'Fakturační 3');
        $this->setField($form, 'user[invoicing_city]', 'Ostrava');
        $this->setField($form, 'user[invoicing_zip]', '70030');
        $this->setField($form, 'user[allow_wholesale]', true);
        $this->setField($form, 'user[is_ceskakruta]', true);
        $this->client->submit($form);

        $this->assertRedirectsTo(self::USERS_URL, self::USER_EDIT);

        $row = $this->fetchRow('user', self::USER_EDIT);
        self::assertSame('pu-edited@places-users.test', $row['email']);
        self::assertSame('PU Upravené Jméno', $row['name']);
        self::assertSame('+420699999999', $row['phone']);
        self::assertSame(22, self::toInt($row['preferred_place_id']));
        self::assertSame('Nová 10', $row['delivery_street']);
        self::assertSame('Olomouc', $row['delivery_city']);
        self::assertSame('77900', $row['delivery_zip']);
        self::assertSame(1, self::toInt($row['company_invoicing']));
        self::assertSame('PU Nová firma a.s.', $row['company_name']);
        self::assertSame('87654321', $row['company_id']);
        self::assertSame('CZ87654321', $row['company_vat_id']);
        self::assertSame('Fakturační 3', $row['invoicing_street']);
        self::assertSame('Ostrava', $row['invoicing_city']);
        self::assertSame('70030', $row['invoicing_zip']);
        self::assertSame(1, self::toInt($row['allow_wholesale']));
        self::assertSame(1, self::toInt($row['is_ceskakruta']));
        self::assertSame(0, self::toInt($row['is_admin']));
        self::assertSame($passwordBefore, $row['password'], 'Shared with the old app: password must stay untouched.');
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
    }

    public function testEmptyPreferredPlaceAndUncheckedFlagsAreStored(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/uzivatele/4004');
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        $this->setField($form, 'user[preferred_place_id]', '');
        $this->setField($form, 'user[company_invoicing]', false);
        $this->setField($form, 'user[allow_wholesale]', false);
        $this->client->submit($form);

        self::assertResponseRedirects();

        $row = $this->fetchRow('user', self::USER_WHOLESALE);
        self::assertNull($row['preferred_place_id']);
        self::assertSame(0, self::toInt($row['company_invoicing']));
        self::assertSame(0, self::toInt($row['allow_wholesale']));
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler, 'Uložit a pokračovat v editaci');
        $this->setField($form, 'user[name]', 'PU Pokračování');
        $this->client->submit($form);

        $this->assertRedirectsTo(self::EDIT_URL);
        self::assertSame('PU Pokračování', $this->fetchRow('user', self::USER_EDIT)['name']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidEmail(): iterable
    {
        yield 'empty' => [''];
        yield 'invalid' => ['not-an-email'];
    }

    #[DataProvider('provideInvalidEmail')]
    public function testEmailRequiredAndValid(string $email): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        $this->setField($form, 'user[email]', $email);
        $this->setField($form, 'user[name]', 'PU Nemá se uložit');
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');

        $row = $this->fetchRow('user', self::USER_EDIT);
        self::assertSame('pu-edit@places-users.test', $row['email']);
        self::assertSame('PU Editovaný', $row['name']);
    }

    public function testPreferredPlaceIncludesWholesaleOption22(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        $options = $this->options($crawler, 'user[preferred_place_id]');

        // Special option 22 replaces the label of place 22, listed once
        self::assertSame('Rozvoz Česká Krůta - Velkoobchod všechny dny', $options[22] ?? null);
        self::assertCount(1, $crawler->filter('select[name="user[preferred_place_id]"] option[value="22"]'));

        // Other options = active places labelled by `title`, ordered `order DESC`
        self::assertSame('PU Prodejna Dny, Dlouhá 1', $options[4001] ?? null);
        self::assertSame('Rychvald - Prodejna, Rychvald 1215', $options[1] ?? null);
        self::assertSame('Rozvoz domů Přerov + okolí do 20 km', $options[7] ?? null);
        self::assertArrayNotHasKey(4005, $options, 'Inactive place.');
        self::assertArrayNotHasKey(4004, $options, 'Deleted place.');

        $own = array_values(array_filter(array_keys($options), static fn (int $id): bool => in_array($id, [4001, 4003, 1, 7, 21], true)));
        self::assertSame([4001, 4003, 1, 7, 21], $own);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnknownUserUrls(): iterable
    {
        yield 'edit' => ['/admin/uzivatele/4999'];
        yield 'banned products' => ['/admin/uzivatele/4999/zakazane-produkty'];
    }

    #[DataProvider('provideUnknownUserUrls')]
    public function testUnknownIdRedirects(string $url): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(302);
        $this->assertRedirectsTo(self::USERS_URL);
    }

    public function testGrantsAndRevokesAdmin(): void
    {
        $this->loginAsAdmin();

        // Grant
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();
        $form = $this->formByButton($crawler);
        self::assertFalse($this->isChecked($form, 'user[is_admin]'), 'Not an admin yet.');
        $this->setField($form, 'user[is_admin]', true);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(1, self::toInt($this->fetchRow('user', self::USER_EDIT)['is_admin']));

        // Revoke another admin
        $crawler = $this->client->request('GET', '/admin/uzivatele/4002');
        self::assertResponseIsSuccessful();
        $form = $this->formByButton($crawler);
        self::assertTrue($this->isChecked($form, 'user[is_admin]'), 'Admin checkbox pre-checked.');
        $this->setField($form, 'user[is_admin]', false);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(0, self::toInt($this->fetchRow('user', self::USER_ADMIN)['is_admin']));
    }

    public function testCannotRevokeOwnAdmin(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', '/admin/uzivatele/' . self::ADMIN_ID);
        self::assertResponseIsSuccessful();

        // Post the own form without `is_admin` (= unchecked), bypassing a possibly disabled checkbox
        $form = $this->formByButton($crawler);
        $values = $form->getPhpValues();
        self::assertIsArray($values['user'] ?? null);
        unset($values['user']['is_admin']);
        $this->submitValues($form, $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, self::toInt($this->fetchRow('user', self::ADMIN_ID)['is_admin']), 'An admin can not remove their own admin flag.');
    }

    /**
     * @return array<int, string>
     */
    private function options(Crawler $crawler, string $name): array
    {
        $select = $crawler->filter(sprintf('select[name="%s"]', $name));
        self::assertCount(1, $select, sprintf('Select "%s" not found.', $name));

        $options = [];
        foreach ($select->filter('option') as $option) {
            assert($option instanceof \DOMElement);
            if ($option->getAttribute('value') !== '') {
                $options[(int) $option->getAttribute('value')] = self::normalize($option->textContent);
            }
        }

        return $options;
    }
}
