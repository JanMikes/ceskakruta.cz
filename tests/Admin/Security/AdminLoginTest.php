<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Security;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;
use Symfony\Component\BrowserKit\Cookie;

/**
 * PLAN §2.1, §3.2 — separate `admin` firewall, admins only.
 */
final class AdminLoginTest extends AdminWebTestCase
{
    public function testLoginPageRenders(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('input[name="_username"]'));
        self::assertCount(1, $crawler->filter('input[name="_password"][type="password"]'));
        self::assertCount(1, $crawler->filter('input[name="_remember_me"][type="checkbox"]'));
        self::assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
        self::assertSelectorTextContains('button[type="submit"]', 'Přihlásit');
    }

    public function testAdminCanLogIn(): void
    {
        $this->submitLogin(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);

        $this->assertRedirectsTo('/admin');
        $this->client->followRedirect();
        self::assertSelectorTextContains('h1', 'Přehled');
    }

    public function testInvalidCredentialsShowError(): void
    {
        $this->submitLogin(self::ADMIN_EMAIL, 'wrong-password');

        $this->assertRedirectsTo('/admin/login');
        $this->client->followRedirect();
        $this->assertFlash('Byly zadány neplatné přihlašovací údaje!', 'danger');

        $this->client->request('GET', '/admin');
        $this->assertRedirectsTo('/admin/login');
    }

    public function testUnknownEmailShowsSameError(): void
    {
        $this->submitLogin('nobody@example.test', 'whatever');

        $this->client->followRedirect();
        $this->assertFlash('Byly zadány neplatné přihlašovací údaje!', 'danger');
    }

    public function testNonAdminCannotLogIn(): void
    {
        $this->submitLogin(self::CUSTOMER_EMAIL, self::CUSTOMER_PASSWORD);

        $this->assertRedirectsTo('/admin/login');
        $this->client->followRedirect();
        $this->assertFlash('Do administrace mají přístup pouze administrátoři.', 'danger');

        $this->client->request('GET', '/admin');
        $this->assertRedirectsTo('/admin/login');
    }

    public function testRedirectsToOriginallyRequestedPageAfterLogin(): void
    {
        $this->client->request('GET', '/admin/objednavky');
        $this->assertRedirectsTo('/admin/login');

        $this->submitLogin(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);

        $this->assertRedirectsTo('/admin/objednavky');
    }

    public function testLoggedAdminVisitingLoginIsRedirectedToDashboard(): void
    {
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/login');

        $this->assertRedirectsTo('/admin');
    }

    public function testRememberMeKeepsAdminLoggedIn(): void
    {
        $this->submitLogin(self::ADMIN_EMAIL, self::ADMIN_PASSWORD, rememberMe: true);
        self::assertResponseRedirects();

        $rememberMe = $this->client->getCookieJar()->get('ADMIN_REMEMBERME');
        self::assertInstanceOf(Cookie::class, $rememberMe);

        // 14 days, like the old "Trvalé přihlášení"
        $expiresIn = (int) $rememberMe->getExpiresTime() - time();
        self::assertGreaterThan(13 * 86400, $expiresIn);
        self::assertLessThanOrEqual(14 * 86400, $expiresIn);

        // Session gone (browser restarted), only the remember-me cookie survives
        $this->client->getCookieJar()->clear();
        $this->client->getCookieJar()->set($rememberMe);

        $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Přehled');
    }

    public function testWithoutRememberMeNoCookieIsSet(): void
    {
        $this->submitLogin(self::ADMIN_EMAIL, self::ADMIN_PASSWORD);
        $this->assertRedirectsTo('/admin');

        self::assertNull($this->client->getCookieJar()->get('ADMIN_REMEMBERME'));
    }

    private function submitLogin(string $email, string $password, bool $rememberMe = false): void
    {
        $this->client->request('GET', '/admin/login');
        self::assertResponseIsSuccessful();

        $values = [
            '_username' => $email,
            '_password' => $password,
        ];

        $form = $this->client->getCrawler()->selectButton('Přihlásit')->form($values);

        if ($rememberMe) {
            $remember = $form['_remember_me'];
            self::assertInstanceOf(\Symfony\Component\DomCrawler\Field\ChoiceFormField::class, $remember);
            $remember->tick();
        }

        $this->client->submit($form);
    }
}
