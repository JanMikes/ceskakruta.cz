<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Validation rules shared by all calendar forms (old ManageCalendar*FormFactory):
 * week_number required int 1–53, year required int 2014…current+2 with default current year.
 */
abstract class CreateCalendarTestCase extends CalendarTestCase
{
    #[DataProvider('provideWeeks')]
    public function testWeekRange1To53(string $week, bool $valid): void
    {
        $this->assertCreateOutcome(['week_number' => $week], $valid);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideWeeks(): iterable
    {
        yield 'empty (required)' => ['', false];
        yield '0' => ['0', false];
        yield '1' => ['1', true];
        yield '53' => ['53', true];
        yield '54' => ['54', false];
        yield 'not an integer' => ['abc', false];
        yield 'decimal' => ['1.5', false];
    }

    #[DataProvider('provideYears')]
    public function testYearRange2014ToCurrentPlus2(string $year, bool $valid): void
    {
        $this->assertCreateOutcome(['year' => $year], $valid);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideYears(): iterable
    {
        $current = (int) date('Y');

        yield 'empty (required)' => ['', false];
        yield '2013' => ['2013', false];
        yield '2014' => ['2014', true];
        yield 'current' => [(string) $current, true];
        yield 'current + 2' => [(string) ($current + 2), true];
        yield 'current + 3' => [(string) ($current + 3), false];
        yield 'not an integer' => ['abc', false];
    }

    public function testDefaultYearIsCurrent(): void
    {
        $crawler = $this->openPage(static::path() . '/pridat');

        $form = $this->formByButton($crawler);
        self::assertSame(date('Y'), $this->fieldValue($form, static::table() . '[year]'));
        self::assertSame('', $this->fieldValue($form, static::table() . '[week_number]'));
    }

    public function testCreatePageHasNoSaveAndContinueButton(): void
    {
        $crawler = $this->openPage(static::path() . '/pridat');

        $this->formByButton($crawler);
        self::assertCount(0, $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text('')) === self::SAVE_AND_CONTINUE,
        ));
    }

    /**
     * Submits the create form with valid values overridden by `$override` and asserts saved (redirect) or rejected (422, nothing saved).
     *
     * @param array<string, string|bool> $override
     */
    protected function assertCreateOutcome(array $override, bool $valid): void
    {
        $before = $this->countRows(static::table());

        $crawler = $this->openPage(static::path() . '/pridat');
        $this->submitAdminForm($crawler, static::table(), $override + static::validValues());

        if ($valid) {
            $this->assertRedirectsTo(static::path(), $this->lastInsertedId(static::table()));
            self::assertSame($before + 1, $this->countRows(static::table()));
        } else {
            $this->assertFormRejected();
            self::assertSame($before, $this->countRows(static::table()));
        }
    }
}
