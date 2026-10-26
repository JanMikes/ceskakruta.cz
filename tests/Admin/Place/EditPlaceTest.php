<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Place;

final class EditPlaceTest extends PlaceTestCase
{
    private const EDIT_URL = '/admin/prodejny/4001';

    public function testEdits(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler);
        self::assertSame('PU Prodejna Dny', $this->fieldValue($form, 'place[name]'));

        // Monday disabled (was enabled), Thursday enabled, Wednesday/Sunday changed
        $this->fillPlaceForm($form, [
            'name' => 'PU Prodejna Upravená',
            'title' => 'PU Prodejna Upravená, Krátká 2',
            'allow_only_wholesale' => true,
            'force_delivery_address' => true,
            'is_cool_balik' => true,
            'force_packing' => true,
            'description' => '<p>Upravený popis</p>',
            'opening_hours' => 'So 8-10',
            'opening_hours_email' => 'So 8-10 (email)',
        ], [
            3 => ['1', '17:00'],
            4 => ['2', '09:45'],
            7 => ['10', '08:15'],
        ]);
        $this->client->submit($form);

        $this->assertRedirectsTo(self::PLACES_URL, self::PLACE_DAYS);

        $row = $this->fetchRow('place', self::PLACE_DAYS);
        self::assertSame('PU Prodejna Upravená', $row['name']);
        self::assertSame('PU Prodejna Upravená, Krátká 2', $row['title']);
        self::assertSame(1, self::toInt($row['allow_only_wholesale']));
        self::assertSame(1, self::toInt($row['force_delivery_address']));
        self::assertSame(1, self::toInt($row['is_cool_balik']));
        self::assertSame(1, self::toInt($row['force_packing']));
        self::assertSame('<p>Upravený popis</p>', $row['description']);
        self::assertSame('So 8-10', $row['opening_hours']);
        self::assertSame('So 8-10 (email)', $row['opening_hours_email']);
        self::assertNull($row['allow_order_day_1_days_before']);
        self::assertNull($row['allow_order_day_1_time']);
        self::assertSame(1, self::toInt($row['allow_order_day_3_days_before']));
        self::assertSame('17:00:00', $row['allow_order_day_3_time']);
        self::assertSame(2, self::toInt($row['allow_order_day_4_days_before']));
        self::assertSame('09:45:00', $row['allow_order_day_4_time']);
        self::assertSame(10, self::toInt($row['allow_order_day_7_days_before']));
        self::assertNull($row['allow_order_day_2_days_before']);
        self::assertNull($row['allow_order_day_2_time']);
        self::assertSame(4500, self::toInt($row['order']), 'Order is not touched by edit.');
        self::assertSame('admin', $row['upd_process']);
        self::assertSame((string) self::ADMIN_ID, $row['upd_user_id']);

        // Prices are not touched on edit
        self::assertSame(4, $this->fetchInt('SELECT COUNT(*) FROM product_place WHERE place_id = :id', ['id' => self::PLACE_DAYS]));

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
    }

    public function testSaveAndContinueStaysOnForm(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        $form = $this->formByButton($crawler, 'Uložit a pokračovat v editaci');
        $this->setField($form, 'place[name]', 'PU Pokračování');
        $this->client->submit($form);

        $this->assertRedirectsTo(self::EDIT_URL);
        self::assertSame('PU Pokračování', $this->fetchRow('place', self::PLACE_DAYS)['name']);

        $this->client->followRedirect();
        $this->assertFlash('Záznam byl úspěšně uložen!');
    }

    public function testEnableDayCheckboxesReflectData(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);
        self::assertResponseIsSuccessful();

        // 4001: Monday 2 / 12:00, Wednesday 0 / 18:30 (0 days before is still enabled), Sunday 9 / 08:15
        $expected = [
            1 => ['2', '12:00'],
            2 => null,
            3 => ['0', '18:30'],
            4 => null,
            5 => null,
            6 => null,
            7 => ['9', '08:15'],
        ];

        foreach ($expected as $day => $values) {
            $enable = $crawler->filter(sprintf('input[type="checkbox"][name="place[enable_day_%d]"]', $day));
            self::assertCount(1, $enable, sprintf('Day %d', $day));

            $daysBefore = $crawler->filter(sprintf('input[name="place[allow_order_day_%d_days_before]"]', $day));
            $time = $crawler->filter(sprintf('input[name="place[allow_order_day_%d_time]"]', $day));
            self::assertCount(1, $daysBefore, sprintf('Day %d', $day));
            self::assertCount(1, $time, sprintf('Day %d', $day));

            if ($values === null) {
                self::assertNull($enable->attr('checked'), sprintf('Day %d is disabled.', $day));
                self::assertSame('23:59', $time->attr('value'), sprintf('Disabled day %d shows the default time.', $day));
            } else {
                self::assertNotNull($enable->attr('checked'), sprintf('Day %d is enabled.', $day));
                self::assertSame($values[0], $daysBefore->attr('value'), sprintf('Day %d', $day));
                self::assertSame($values[1], $time->attr('value'), sprintf('Day %d time is HH:MM.', $day));
            }
        }
    }

    public function testNoPriceCopyFieldOnEdit(): void
    {
        $this->loginAsAdmin();
        $crawler = $this->client->request('GET', self::EDIT_URL);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('input[name="place[name]"]'));
        self::assertCount(0, $crawler->filter('[name="place[prices_from_place_id]"]'));
    }

    public function testUnknownIdRedirectsToList(): void
    {
        $this->loginAsAdmin();
        $this->client->request('GET', '/admin/prodejny/4999');

        self::assertResponseStatusCodeSame(302);
        $this->assertRedirectsTo(self::PLACES_URL);
    }
}
