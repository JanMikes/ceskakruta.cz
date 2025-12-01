<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Value\Place;

final class WholesaleDelivery
{
    /**
     * @return array<int>
     */
    public function getAllowedDaysForWholesalePlace(Place $place): array
    {
        $allowedDays = [];

        if ($place->day1AllowedDaysBefore !== null) {
            $allowedDays[] = 1;
        }
        if ($place->day2AllowedDaysBefore !== null) {
            $allowedDays[] = 2;
        }
        if ($place->day3AllowedDaysBefore !== null) {
            $allowedDays[] = 3;
        }
        if ($place->day4AllowedDaysBefore !== null) {
            $allowedDays[] = 4;
        }
        if ($place->day5AllowedDaysBefore !== null) {
            $allowedDays[] = 5;
        }

        return $allowedDays;
    }
}
