<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Unit\Admin;

use CeskaKruta\Web\Value\Admin\IsoWeekRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IsoWeekRangeTest extends TestCase
{
    #[DataProvider('provideWeeks')]
    public function testFormat(int $week, int $year, string $expected): void
    {
        self::assertSame($expected, IsoWeekRange::format($week, $year));
    }

    /**
     * Expected values = output of the old admin (PHP `DateTime::setISODate()` Monday / Sunday, formats `d.m.` and `d.m. Y`).
     *
     * @return iterable<string, array{int, int, string}>
     */
    public static function provideWeeks(): iterable
    {
        yield 'regular week' => [10, 2024, '10/2024 (04.03. - 10.03. 2024)'];
        yield 'single digit week, leading zeros in dates' => [5, 2029, '5/2029 (29.01. - 04.02. 2029)'];
        yield 'last week fully in december' => [52, 2024, '52/2024 (23.12. - 29.12. 2024)'];
        yield 'week 1 starting in previous december (2025)' => [1, 2025, '1/2025 (30.12. - 05.01. 2025)'];
        yield 'week 1 starting in previous december (2026)' => [1, 2026, '1/2026 (29.12. - 04.01. 2026)'];
        yield 'week 1 starting on 31.12.' => [1, 2030, '1/2030 (31.12. - 06.01. 2030)'];
        yield 'week 1 starting in january' => [1, 2021, '1/2021 (04.01. - 10.01. 2021)'];
        yield 'week 53 ending in next year (2020)' => [53, 2020, '53/2020 (28.12. - 03.01. 2021)'];
        yield 'week 53 ending in next year (2026)' => [53, 2026, '53/2026 (28.12. - 03.01. 2027)'];
        yield 'week 53 of a 52-week year overflows like the old helper' => [53, 2025, '53/2025 (29.12. - 04.01. 2026)'];
    }
}
