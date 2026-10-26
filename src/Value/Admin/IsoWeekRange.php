<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Label of an ISO week (Monday–Sunday) as rendered by the old admin calendar lists
 * (old `TemplateHelper::weekStart/weekEnd` + `{$week}/{$year} ({start|date:'d.m.'} - {end|date:'d.m. Y'})`),
 * e.g. "1/2025 (30.12. - 05.01. 2025)". The year at the end is the year of the week's Sunday.
 */
final class IsoWeekRange
{
    public static function format(int $week, int $year): string
    {
        throw new \LogicException('Not implemented yet');
    }
}
