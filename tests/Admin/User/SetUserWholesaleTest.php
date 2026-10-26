<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\User;

final class SetUserWholesaleTest extends UserTestCase
{
    public function testAllow(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::USERS_URL);
        self::assertResponseIsSuccessful();

        $form = self::single($crawler->filter('form[action="/admin/uzivatele/4003/velkoobchod"]'), 'wholesale form')->form();
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(self::USERS_URL, $this->locationPath());

        $row = $this->fetchRow('user', self::USER_PLAIN);
        self::assertSame(1, self::toInt($row['allow_wholesale']));
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);

        $this->client->followRedirect();
        $this->assertFlash('Velkoprodej byl povolen pro uživatele: pu-plain@places-users.test');
    }

    public function testCancel(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::USERS_URL);
        self::assertResponseIsSuccessful();

        $form = self::single($crawler->filter('form[action="/admin/uzivatele/4004/velkoobchod"]'), 'wholesale form')->form();
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(self::USERS_URL, $this->locationPath());
        self::assertSame(0, self::toInt($this->fetchRow('user', self::USER_WHOLESALE)['allow_wholesale']));

        $this->client->followRedirect();
        $this->assertFlash('Velkoprodej byl zakázán pro uživatele: pu-wholesale@places-users.test');
    }
}
