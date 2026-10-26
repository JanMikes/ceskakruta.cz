<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Place;

final class PlacesPageTest extends PlaceTestCase
{
    public function testShowsClosedDaysFromTwoDaysAgo(): void
    {
        $threeDaysAgo = $this->insert('place_closed_day', ['place_id' => self::PLACE_DAYS, 'date' => date('Y-m-d', strtotime('-3 days'))]);
        $twoDaysAgo = $this->insert('place_closed_day', ['place_id' => self::PLACE_DAYS, 'date' => date('Y-m-d', strtotime('-2 days'))]);
        $inFiveDays = $this->insert('place_closed_day', ['place_id' => 1, 'date' => date('Y-m-d', strtotime('+5 days'))]);
        $tomorrow = $this->insert('place_closed_day', ['place_id' => null, 'date' => date('Y-m-d', strtotime('+1 day'))]);
        $deleted = $this->insert('place_closed_day', ['place_id' => 1, 'date' => date('Y-m-d', strtotime('+2 days')), 'del_flag' => 1]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACES_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('h1', 'Prodejny');

        $ids = $this->closedDayIds($crawler);
        self::assertNotContains($threeDaysAgo, $ids, 'Closed days older than today − 2 days are hidden.');
        self::assertNotContains($deleted, $ids, 'Deleted closed days are hidden.');

        // Sorted date ASC (fixture closed day #1 is 2099-12-24 → last)
        $own = array_values(array_filter($ids, static fn (int $id): bool => in_array($id, [$twoDaysAgo, $tomorrow, $inFiveDays, 1], true)));
        self::assertSame([$twoDaysAgo, $tomorrow, $inFiveDays, 1], $own);

        // Column "Datum zavřeno" d.m.Y, "Prodejna" = place name
        $row = self::normalize($this->closedDayRow($crawler, $inFiveDays)->text());
        self::assertStringContainsString(date('d.m.Y', strtotime('+5 days')), $row);
        self::assertStringContainsString('Rychvald - Prodejna', $row);

        // Delete action per row, "Přidat výjimku" + "Přidat prodejnu" links
        $this->gridActionForm($crawler, 'place_closed_day', $twoDaysAgo, 'delete');
        $addClosedDay = $crawler->filter(sprintf('a[href="%s"]', self::CLOSED_DAY_ADD_URL));
        self::assertGreaterThan(0, $addClosedDay->count());
        self::assertStringContainsString('Přidat výjimku', $addClosedDay->text());
        self::assertGreaterThan(0, $crawler->filter(sprintf('a[href="%s"]', self::PLACE_ADD_URL))->count());
    }

    public function testClosedDayAllPlacesLabel(): void
    {
        $allPlaces = $this->insert('place_closed_day', ['place_id' => null, 'date' => date('Y-m-d', strtotime('+3 days'))]);
        $onePlace = $this->insert('place_closed_day', ['place_id' => self::PLACE_DAYS, 'date' => date('Y-m-d', strtotime('+4 days'))]);

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACES_URL);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Všechny', $this->closedDayRow($crawler, $allPlaces)->text());

        $placeRow = $this->closedDayRow($crawler, $onePlace)->text();
        self::assertStringContainsString('PU Prodejna Dny', $placeRow);
        self::assertStringNotContainsString('Všechny', $placeRow);
    }

    public function testHidesHideInAdminPlaces(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACES_URL);

        self::assertResponseIsSuccessful();

        $ids = $this->listedIds($crawler);
        self::assertNotContains(self::PLACE_HIDDEN, $ids);
        self::assertNotContains(30, $ids, 'Fixture place 30 has hide_in_admin = 1.');
        self::assertNotContains(self::PLACE_DELETED, $ids);

        // Inactive places are listed (with an activate button), sorted `order DESC`
        $this->assertListedInOrder($crawler, [self::PLACE_DAYS, self::PLACE_COOL, self::PLACE_INACTIVE, 1, 7, 21, 22]);
        $this->gridActionForm($crawler, 'place', self::PLACE_INACTIVE, 'activate');
        $this->gridActionForm($crawler, 'place', self::PLACE_DAYS, 'deactivate');
        $this->gridActionForm($crawler, 'place', self::PLACE_DAYS, 'move-down');
        self::assertCount(1, $this->placeRow($crawler, self::PLACE_DAYS)->filter(sprintf('a[href="/admin/prodejny/%d"]', self::PLACE_DAYS)));

        // Places can not be deleted
        $this->assertNoGridAction($crawler, 'place', self::PLACE_DAYS, 'delete');
        $this->assertNoGridAction($crawler, 'place', 1, 'delete');
    }

    public function testDeliveryColumn(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACES_URL);

        self::assertResponseIsSuccessful();

        $shop = $this->cellTexts($this->placeRow($crawler, self::PLACE_DAYS));
        self::assertSame('PU Prodejna Dny', $shop[0]);
        self::assertSame('Ne', $shop[1]);

        $cool = $this->cellTexts($this->placeRow($crawler, self::PLACE_COOL));
        self::assertSame('Ano (Cool balík)', $cool[1]);

        $delivery = $this->cellTexts($this->placeRow($crawler, 7));
        self::assertSame('Ano', $delivery[1]);
    }

    public function testDayColumnsShowResultingWeekdayAndTime(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACES_URL);

        self::assertResponseIsSuccessful();

        $cells = $this->cellTexts($this->placeRow($crawler, self::PLACE_DAYS));
        self::assertGreaterThanOrEqual(9, count($cells));

        // Monday: 2 days before at 12:00 → Saturday
        self::assertStringContainsString('Povoleno', $cells[2]);
        self::assertStringContainsString('Dnů před: 2', $cells[2]);
        self::assertStringContainsString('(Sobota 12:00)', $cells[2]);
        // Tuesday disabled
        self::assertSame('-', $cells[3]);
        // Wednesday: 0 days before (still enabled) at 18:30 → Wednesday
        self::assertStringContainsString('Dnů před: 0', $cells[4]);
        self::assertStringContainsString('(Středa 18:30)', $cells[4]);
        // Thursday – Saturday disabled
        self::assertSame('-', $cells[5]);
        self::assertSame('-', $cells[6]);
        self::assertSame('-', $cells[7]);
        // Sunday: 9 days before at 08:15 → Friday
        self::assertStringContainsString('Dnů před: 9', $cells[8]);
        self::assertStringContainsString('(Pátek 08:15)', $cells[8]);

        // Fixture place 21: Tuesday 3 days before 10:00 → Saturday, Friday 3 days before → Tuesday
        $cool = $this->cellTexts($this->placeRow($crawler, 21));
        self::assertStringContainsString('(Sobota 10:00)', $cool[3]);
        self::assertStringContainsString('(Úterý 10:00)', $cool[6]);
    }
}
