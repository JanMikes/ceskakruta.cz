<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Ordering rule of one delivery weekday of a place, `daysBefore = null` = the day is not offered.
 */
readonly final class AdminPlaceDay
{
    /**
     * @param int<1, 7> $day
     * @param null|string $time HH:MM
     */
    public function __construct(
        public int $day,
        public null|int $daysBefore,
        public null|string $time,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->daysBefore !== null;
    }

    public function dayName(): string
    {
        return PlaceDayHint::dayName($this->day);
    }

    public function orderDayName(): null|string
    {
        if ($this->daysBefore === null) {
            return null;
        }

        return PlaceDayHint::dayName(PlaceDayHint::orderDay($this->day, $this->daysBefore));
    }
}
