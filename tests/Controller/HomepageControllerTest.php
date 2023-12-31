<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomepageControllerTest extends WebTestCase
{
    public function testPageCanBeRenderedWithoutLogin(): void
    {
        $client = self::createClient();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
