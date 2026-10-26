<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Shell;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PLAN §2.2 — the `user` table is shared with the old app, so the legacy salted MD5 hash must stay.
 */
final class ChangePasswordTest extends AdminWebTestCase
{
    public function testChangesPassword(): void
    {
        $this->loginAsAdmin();

        $this->submit(self::ADMIN_PASSWORD, 'new-secret', 'new-secret');

        $this->assertRedirectsTo('/admin/zmena-hesla');
        $this->client->followRedirect();
        $this->assertFlash('Heslo bylo úspěšně změněno!');

        // Same format as fixtures / old app: MD5(SECURITY_SALT_1 . password . SECURITY_SALT_2), test salts from .env.test
        self::assertSame(md5('test1' . 'new-secret' . 'test2'), $this->fetchRow('user', self::ADMIN_ID)['password']);

        // Stays logged in
        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
    }

    public function testRejectsWrongOldPassword(): void
    {
        $this->loginAsAdmin();
        $before = $this->fetchRow('user', self::ADMIN_ID)['password'];

        $this->submit('not-my-password', 'new-secret', 'new-secret');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.invalid-feedback', 'Bylo zadáno chybné staré heslo!');
        self::assertSame($before, $this->fetchRow('user', self::ADMIN_ID)['password']);
    }

    public function testRejectsMismatch(): void
    {
        $this->loginAsAdmin();
        $before = $this->fetchRow('user', self::ADMIN_ID)['password'];

        $this->submit(self::ADMIN_PASSWORD, 'new-secret', 'other-secret');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($before, $this->fetchRow('user', self::ADMIN_ID)['password']);
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function invalidLengths(): \Generator
    {
        yield 'old password shorter than 4' => ['abc', 'new-secret'];
        yield 'new password shorter than 4' => [self::ADMIN_PASSWORD, 'abc'];
        yield 'new password longer than 20' => [self::ADMIN_PASSWORD, str_repeat('a', 21)];
    }

    #[DataProvider('invalidLengths')]
    public function testValidatesLength(string $oldPassword, string $newPassword): void
    {
        $this->loginAsAdmin();
        $before = $this->fetchRow('user', self::ADMIN_ID)['password'];

        $this->submit($oldPassword, $newPassword, $newPassword);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($before, $this->fetchRow('user', self::ADMIN_ID)['password']);
    }

    private function submit(string $old, string $new, string $again): void
    {
        $this->client->request('GET', '/admin/zmena-hesla');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Uložit', [
            'change_password[old_password]' => $old,
            'change_password[new_password][first]' => $new,
            'change_password[new_password][second]' => $again,
        ]);
    }
}
