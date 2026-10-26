<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Place;

final class DeletePlaceClosedDayTest extends PlaceTestCase
{
    public function testDeletesClosedDay(): void
    {
        $id = $this->insert('place_closed_day', ['place_id' => self::PLACE_DAYS, 'date' => date('Y-m-d', strtotime('+6 days'))]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACES_URL);
        self::assertResponseIsSuccessful();

        $this->client->submit($this->gridActionForm($crawler, 'place_closed_day', $id, 'delete'));

        self::assertResponseRedirects();
        self::assertSame(self::PLACES_URL . '#closed-day-' . $id, (string) preg_replace('~^https?://[^/]+~', '', (string) $this->client->getResponse()->headers->get('Location')));
        self::assertSame(1, self::toInt($this->fetchRow('place_closed_day', $id)['del_flag']), 'Soft delete.');

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam úspěšně smazán');
        self::assertNotContains($id, $this->closedDayIds($crawler));
    }
}
