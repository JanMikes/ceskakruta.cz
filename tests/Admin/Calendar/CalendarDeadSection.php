<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Tests\Admin\Calendar;

trait CalendarDeadSection
{
    protected static function table(): string
    {
        return 'calendar_dead';
    }

    protected static function path(): string
    {
        return '/admin/rozkrmena-krutata';
    }

    /**
     * @return array<string, string|bool>
     */
    protected static function validValues(): array
    {
        return [
            'farma_doubrava' => true,
            'farma_rychvald' => false,
            'week_number' => '12',
            'year' => date('Y'),
            'week_age' => '7',
            'weight' => '3-4 kg',
            'price_mix' => '120 Kč',
            'price_man' => '130 Kč',
            'price_woman' => '110 Kč',
            'note' => 'Nová poznámka',
        ];
    }
}
