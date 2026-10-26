<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

trait CalendarColdSection
{
    protected static function table(): string
    {
        return 'calendar_cold';
    }

    protected static function path(): string
    {
        return '/admin/chlazene-kruty';
    }

    /**
     * @return array<string, string|bool>
     */
    protected static function validValues(): array
    {
        return [
            'product_tp_id' => '2',
            'week_number' => '12',
            'year' => date('Y'),
            'weight_from' => '6',
            'weight_to' => '8.5',
        ];
    }
}
