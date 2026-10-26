<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

trait CalendarAliveSection
{
    protected static function table(): string
    {
        return 'calendar_alive';
    }

    protected static function path(): string
    {
        return '/admin/zive-kruty';
    }

    /**
     * @return array<string, string|bool>
     */
    protected static function validValues(): array
    {
        return [
            'product_tp_id' => '2',
            'farma_doubrava' => false,
            'farma_rychvald' => true,
            'week_number' => '12',
            'year' => date('Y'),
            'weight_from' => '6',
            'weight_to' => '8.5',
        ];
    }
}
