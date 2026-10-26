<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Place;

use PHPUnit\Framework\Attributes\DataProvider;

final class CreatePlaceTest extends PlaceTestCase
{
    public function testCreates(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, [
            'prices_from_place_id' => '',
            'name' => 'PU Nová prodejna',
            'title' => 'PU Nová prodejna, Nová 1',
            'allow_only_wholesale' => true,
            'force_delivery_address' => true,
            'is_cool_balik' => false,
            'force_packing' => true,
            'description' => '<p>Nový popis</p>',
            'opening_hours' => 'Po-Pá 9-17',
            'opening_hours_email' => 'Po-Pá 9-17 (email)',
        ], [
            2 => ['1', '10:30'],
            5 => ['3', '23:59'],
        ]);
        $this->client->submit($form);

        $id = $this->lastInsertedId('place');
        self::assertGreaterThan(self::PLACE_INACTIVE, $id);
        $this->assertRedirectsTo(self::PLACES_URL, $id);

        $row = $this->fetchRow('place', $id);
        self::assertSame('PU Nová prodejna', $row['name']);
        self::assertSame('PU Nová prodejna, Nová 1', $row['title']);
        self::assertSame(1, self::toInt($row['allow_only_wholesale']));
        self::assertSame(1, self::toInt($row['force_delivery_address']));
        self::assertSame(0, self::toInt($row['is_cool_balik']));
        self::assertSame(1, self::toInt($row['force_packing']));
        self::assertSame('<p>Nový popis</p>', $row['description']);
        self::assertSame('Po-Pá 9-17', $row['opening_hours']);
        self::assertSame('Po-Pá 9-17 (email)', $row['opening_hours_email']);
        self::assertSame(1, self::toInt($row['allow_order_day_2_days_before']));
        self::assertSame('10:30:00', $row['allow_order_day_2_time']);
        self::assertSame(3, self::toInt($row['allow_order_day_5_days_before']));
        self::assertSame('23:59:00', $row['allow_order_day_5_time']);
        foreach ([1, 3, 4, 6, 7] as $day) {
            self::assertNull($row[sprintf('allow_order_day_%d_days_before', $day)], sprintf('Day %d', $day));
            self::assertNull($row[sprintf('allow_order_day_%d_time', $day)], sprintf('Day %d', $day));
        }
        self::assertSame($id * 10, self::toInt($row['order']), 'Orderable table: order = id*10 after insert.');
        self::assertSame(0, self::toInt($row['hide_in_admin']));
        self::assertSame(1, self::toInt($row['active_flag']));
        self::assertSame(0, self::toInt($row['del_flag']));
        self::assertSame('admin', $row['ins_process']);
        self::assertSame((string) self::ADMIN_ID, $row['ins_user_id']);

        self::assertSame(0, $this->fetchInt('SELECT COUNT(*) FROM product_place WHERE place_id = :id', ['id' => $id]), 'No prices copied without a source place.');

        $crawler = $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
        self::assertContains($id, $this->listedIds($crawler));
    }

    public function testCopiesPricesFromPlace(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        self::assertResponseIsSuccessful();

        $options = $this->selectOptions($crawler, 'place[prices_from_place_id]');
        self::assertArrayHasKey(self::PLACE_DAYS, $options);

        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, [
            'prices_from_place_id' => (string) self::PLACE_DAYS,
            'name' => 'PU Kopie cen',
            'title' => 'PU Kopie cen',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $id = $this->lastInsertedId('place');

        // Only active, non-deleted product_place rows of the source place are copied
        $prices = $this->connection()->fetchAllKeyValue(
            'SELECT product_id, price FROM product_place WHERE place_id = :id ORDER BY product_id',
            ['id' => $id],
        );
        self::assertEquals([100 => 300, 101 => 95], $prices);

        $copied = $this->connection()->fetchAllAssociative('SELECT active_flag, del_flag FROM product_place WHERE place_id = :id', ['id' => $id]);
        foreach ($copied as $copy) {
            self::assertSame(1, self::toInt($copy['active_flag']));
            self::assertSame(0, self::toInt($copy['del_flag']));
        }

        // Source untouched
        self::assertSame(4, $this->fetchInt('SELECT COUNT(*) FROM product_place WHERE place_id = :id', ['id' => self::PLACE_DAYS]));
    }

    public function testDisabledDayStoresNulls(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, ['name' => 'PU Vypnuté dny', 'title' => 'PU Vypnuté dny'], [1 => ['2', '12:00']]);

        // Values typed into a disabled day are ignored
        $this->setField($form, 'place[enable_day_3]', false);
        $this->setField($form, 'place[allow_order_day_3_days_before]', '4');
        $this->setField($form, 'place[allow_order_day_3_time]', '08:00');
        $this->client->submit($form);

        self::assertResponseRedirects();

        $row = $this->fetchRow('place', $this->lastInsertedId('place'));
        self::assertSame(2, self::toInt($row['allow_order_day_1_days_before']));
        self::assertSame('12:00:00', $row['allow_order_day_1_time']);
        // The column default is '23:59:00' → NULL must be written explicitly
        self::assertNull($row['allow_order_day_3_days_before']);
        self::assertNull($row['allow_order_day_3_time']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidTime(): iterable
    {
        yield 'hour 24' => ['24:00'];
        yield 'minute 60' => ['12:60'];
        yield 'no colon' => ['1230'];
        yield 'letters' => ['ab:cd'];
        yield 'seconds' => ['12:30:00'];
    }

    #[DataProvider('provideInvalidTime')]
    public function testTimeFormatValidation(string $time): void
    {
        $before = $this->countRows('place');

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, ['name' => 'PU Špatný čas', 'title' => 'PU Špatný čas'], [4 => ['1', $time]]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($before, $this->countRows('place'));
    }

    /**
     * Old pattern `^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$` also accepts a single-digit hour.
     */
    public function testSingleDigitHourIsAccepted(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, ['name' => 'PU Krátký čas', 'title' => 'PU Krátký čas'], [6 => ['1', '7:05']]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame('07:05:00', $this->fetchRow('place', $this->lastInsertedId('place'))['allow_order_day_6_time']);
    }

    public function testDaysBeforeMustBeInteger(): void
    {
        $before = $this->countRows('place');

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, ['name' => 'PU Špatné dny', 'title' => 'PU Špatné dny'], [2 => ['dva', '12:00']]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
        self::assertSame($before, $this->countRows('place'));
    }

    public function testNameAndTitleRequired(): void
    {
        $before = $this->countRows('place');

        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, ['name' => '', 'title' => '']);
        $crawler = $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertGreaterThanOrEqual(2, $crawler->filter('.invalid-feedback')->count());
        self::assertSame($before, $this->countRows('place'));
    }

    public function testDefaultTime2359(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::PLACE_ADD_URL);
        self::assertResponseIsSuccessful();

        for ($day = 1; $day <= 7; $day++) {
            $time = $crawler->filter(sprintf('input[name="place[allow_order_day_%d_time]"]', $day));
            self::assertCount(1, $time);
            self::assertSame('23:59', $time->attr('value'), sprintf('Day %d', $day));

            $enable = $crawler->filter(sprintf('input[type="checkbox"][name="place[enable_day_%d]"]', $day));
            self::assertCount(1, $enable);
            self::assertNull($enable->attr('checked'), sprintf('Day %d is disabled on create.', $day));
        }

        // Enabled day with an empty time → 23:59
        $form = $this->formByButton($crawler);
        $this->fillPlaceForm($form, ['name' => 'PU Bez času', 'title' => 'PU Bez času'], [1 => ['2', '']]);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $row = $this->fetchRow('place', $this->lastInsertedId('place'));
        self::assertSame(2, self::toInt($row['allow_order_day_1_days_before']));
        self::assertSame('23:59:00', $row['allow_order_day_1_time']);
    }
}
