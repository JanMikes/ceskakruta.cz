<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Place;
use DateTimeImmutable;

readonly final class GetAvailableDays
{
    public function __construct(
        private GetPlaces $getPlaces,
    ) {
    }

    /**
     * @return array<DateTimeImmutable>
     */
    public function forPlace(int $placeId): array
    {
        $place = $this->getPlaces->oneById($placeId);

        $availableDays = [];
        $date = new DateTimeImmutable(); // začneme dnešním dnem

        $iterations = 0;
        while (count($availableDays) < 5) {
            if ($this->isDateAvailable($date, $place)) {
                $availableDays[] = $date;
            }

            $date = $date->modify('+1 day');
            $iterations++;

            // failsafe
            if ($iterations > 50) {
                break;
            }
        }

        return $availableDays;
    }

    public function isDateAvailable(DateTimeImmutable $date, Place $place): bool
    {
        $weekDay = (int) $date->format('w');

        // Dny v týdnu k vynechání: 0 = neděle, 1 = pondělí, ..., 6 = sobota
        $skipWeekDays = [1, 6, 0]; // Příklad pro vynechání pondělí, soboty a neděle

        $skipDates = [
            '2024-01-01',
            '2024-05-08',
        ];

        if (in_array($weekDay, $skipWeekDays) || in_array($date->format('Y-m-d'), $skipDates)) {
            return false;
        }

        return true;
    }
}
