<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Security;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

final class AdminLogoutTest extends AdminWebTestCase
{
    public function testLogout(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/logout');

        $this->assertRedirectsTo('/admin/login');

        $this->client->request('GET', '/admin');
        $this->assertRedirectsTo('/admin/login');
    }
}
