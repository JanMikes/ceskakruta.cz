<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

abstract class EditCalendarTestCase extends CalendarTestCase
{
    public function testSaveAndContinueStaysOnForm(): void
    {
        $crawler = $this->openPage(static::path() . '/' . self::FIXTURE_ID);
        $this->submitAdminForm($crawler, static::table(), ['week_number' => '33'], self::SAVE_AND_CONTINUE);

        $this->assertRedirectsTo(static::path() . '/' . self::FIXTURE_ID);
        self::assertSame(33, self::intValue($this->fetchRow(static::table(), self::FIXTURE_ID)['week_number']));

        $this->client->followRedirect();
        $this->assertFlash(self::SAVED_FLASH);
    }

    public function testUnknownIdRedirectsToList(): void
    {
        $this->client->request('GET', static::path() . '/999999');

        $this->assertRedirectsTo(static::path());
    }

    public function testDeletedRowRedirectsToList(): void
    {
        $this->connection()->update(static::table(), ['del_flag' => 1], ['id' => self::FIXTURE_ID]);

        $this->client->request('GET', static::path() . '/' . self::FIXTURE_ID);

        $this->assertRedirectsTo(static::path());
    }

    public function testInvalidEditKeepsRow(): void
    {
        $before = $this->fetchRow(static::table(), self::FIXTURE_ID);

        $crawler = $this->openPage(static::path() . '/' . self::FIXTURE_ID);
        $this->submitAdminForm($crawler, static::table(), ['week_number' => '54']);

        $this->assertFormRejected();
        self::assertSame($before, $this->fetchRow(static::table(), self::FIXTURE_ID));
    }
}
