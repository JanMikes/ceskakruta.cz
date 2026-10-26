<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Place;

final class AddPlaceClosedDayTest extends PlaceTestCase
{
    public function testFormOffersActivePlacesOrderedWithAllPlacesPrompt(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::CLOSED_DAY_ADD_URL);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('select[name="place_closed_day[place_id]"] option[value=""]', '- Všechny prodejny -');

        $options = $this->selectOptions($crawler, 'place_closed_day[place_id]');
        self::assertSame('PU Prodejna Dny', $options[self::PLACE_DAYS] ?? null);
        self::assertArrayNotHasKey(self::PLACE_INACTIVE, $options);
        self::assertArrayNotHasKey(self::PLACE_DELETED, $options);

        $own = array_values(array_filter(array_keys($options), static fn (int $id): bool => in_array($id, [self::PLACE_DAYS, self::PLACE_COOL, 1, 7], true)));
        self::assertSame([self::PLACE_DAYS, self::PLACE_COOL, 1, 7], $own, 'Places ordered by `order DESC`.');
    }

    public function testAddsForPlace(): void
    {
        $date = date('Y-m-d', strtotime('+10 days'));

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::CLOSED_DAY_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->setField($form, 'place_closed_day[date]', $date);
        $this->setField($form, 'place_closed_day[place_id]', (string) self::PLACE_DAYS);
        $this->client->submit($form);

        $id = $this->lastInsertedId('place_closed_day');
        self::assertResponseRedirects();
        self::assertSame(self::PLACES_URL . '#closed-day-' . $id, (string) preg_replace('~^https?://[^/]+~', '', (string) $this->client->getResponse()->headers->get('Location')));

        $row = $this->fetchRow('place_closed_day', $id);
        self::assertSame($date, $row['date']);
        self::assertSame(self::PLACE_DAYS, self::toInt($row['place_id']));
        self::assertSame('admin', $row['ins_process']);
        self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);
        self::assertSame(1, self::toInt($row['active_flag']));
        self::assertSame(0, self::toInt($row['del_flag']));

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertContains($id, $this->closedDayIds($crawler));
    }

    public function testAddsForAllPlaces(): void
    {
        $date = date('Y-m-d', strtotime('+11 days'));

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::CLOSED_DAY_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->setField($form, 'place_closed_day[date]', $date);
        $this->setField($form, 'place_closed_day[place_id]', '');
        $this->client->submit($form);

        self::assertResponseRedirects();

        $row = $this->fetchRow('place_closed_day', $this->lastInsertedId('place_closed_day'));
        self::assertSame($date, $row['date']);
        self::assertNull($row['place_id']);
    }

    public function testDateRequired(): void
    {
        $before = $this->countRows('place_closed_day');

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::CLOSED_DAY_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->setField($form, 'place_closed_day[date]', '');
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($before, $this->countRows('place_closed_day'));
    }
}
