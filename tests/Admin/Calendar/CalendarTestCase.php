<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

use CeskaKruta\Web\Tests\Support\AdminWebTestCase;

/**
 * Common setup of the three calendar sections (PLAN §2.6–2.8): calendar_dead, calendar_cold, calendar_alive.
 */
abstract class CalendarTestCase extends AdminWebTestCase
{
    use AdminFormHelpers;

    /** Edit target row of every calendar table (tests/database/fixtures/20-catalog-simple.sql) */
    protected const FIXTURE_ID = 2001;

    /** DB table = grid action table = form name (PLAN §4.6) */
    abstract protected static function table(): string;

    /** List URL, e.g. /admin/rozkrmena-krutata */
    abstract protected static function path(): string;

    /**
     * Complete valid create values keyed by column name.
     *
     * @return array<string, string|bool>
     */
    abstract protected static function validValues(): array;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAsAdmin();
    }
}
