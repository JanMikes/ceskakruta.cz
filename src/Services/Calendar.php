<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Value\Week;
use DateTimeImmutable;

readonly final class Calendar
{
    public function getCurrentWeek(): Week
    {
        $now = new DateTimeImmutable();

        if ((int) $now->format('N') > 1) {
            $now = new DateTimeImmutable('next monday');
        }

        return new Week((int) $now->format('W'), (int) $now->format('Y'));
    }
}
