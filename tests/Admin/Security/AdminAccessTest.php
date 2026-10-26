<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Security;

use CeskaKruta\Web\Tests\Admin\AdminPages;
use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProviderExternal;

/**
 * In the old admin any logged-in user (customers included) could open the administration — PLAN §1.4.
 */
final class AdminAccessTest extends AdminWebTestCase
{
    #[DataProviderExternal(AdminPages::class, 'protectedGetUrlsProvider')]
    public function testAnonymousIsRedirectedToLogin(string $url): void
    {
        $this->client->request('GET', $url);

        $this->assertRedirectsTo('/admin/login');
    }

    #[DataProviderExternal(AdminPages::class, 'protectedGetUrlsProvider')]
    public function testNonAdminIsForbidden(string $url): void
    {
        $this->loginAsCustomer();

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
    }

    #[DataProviderExternal(AdminPages::class, 'mutatingUrlsProvider')]
    public function testMutatingActionsRejectGet(string $url): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(405);
    }

    #[DataProviderExternal(AdminPages::class, 'mutatingUrlsProvider')]
    public function testMutatingActionsRequireCsrfToken(string $url): void
    {
        $this->loginAsAdmin();
        $before = $this->snapshot();

        $this->client->request('POST', $url, ['_token' => 'invalid', 'allow' => '1', 'orders' => ['1']]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->snapshot(), 'Nothing may change without a valid CSRF token.');
        self::assertSame([], \CeskaKruta\Web\Tests\Support\HttpMock::requests(), 'No external call without a valid CSRF token.');
    }

    #[DataProviderExternal(AdminPages::class, 'mutatingUrlsProvider')]
    public function testMutatingActionsRequireAdmin(string $url): void
    {
        $this->loginAsCustomer();

        $this->client->request('POST', $url);

        self::assertResponseStatusCodeSame(403);
    }

    public function testUnknownGridTableIsNotFound(): void
    {
        $this->loginAsAdmin();

        // The same action on a whitelisted table exists (refused only because of the missing token)…
        $this->client->request('POST', '/admin/record/actuality/1/deactivate');
        self::assertResponseStatusCodeSame(403);

        // …but `user` is a real table that is not whitelisted for generic grid actions
        $this->client->request('POST', '/admin/record/user/1/deactivate');
        self::assertResponseStatusCodeSame(404);
    }

    public function testCustomerFrontendLoginIsNotAnAdminLogin(): void
    {
        // Logging in on the shop (main firewall) must not give access to the admin firewall
        $this->client->request('GET', '/prihlaseni');
        self::assertResponseIsSuccessful();
        $this->client->submitForm('Přihlásit', [
            '_username' => self::ADMIN_EMAIL,
            '_password' => self::ADMIN_PASSWORD,
        ]);

        $this->client->request('GET', '/admin');

        $this->assertRedirectsTo('/admin/login');
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'actuality' => $this->fetchRow('actuality', 1),
            'vendor' => $this->fetchRow('vendor', 1),
            'user' => $this->fetchRow('user', 2),
            'order' => $this->fetchRow('order', 1),
        ];
    }
}
