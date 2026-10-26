<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Infrastructure;

use CeskaKruta\Web\Tests\Support\WebTestCase;

final class WebTestClientTest extends WebTestCase
{
    public function testHomepageRendersInTestEnvironment(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
