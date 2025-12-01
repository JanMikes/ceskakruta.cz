<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\Place;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

readonly final class OrderingDeadline
{
    public function __construct(
        private ClockInterface $clock,
        private GetPlaces $getPlaces,
    ) {
    }

    public function nextOrderDay(int $dayOfWeek, int $placeId): DateTimeImmutable
    {
        $nextDeadline = $this->nextDeadline($dayOfWeek, $placeId);

        $deadlineDay = (int) $nextDeadline->format('N');
        $daysUntilOrder = ($dayOfWeek - $deadlineDay + 7) % 7;
        if ($daysUntilOrder === 0) {
            $daysUntilOrder = 7;
        }

        return $nextDeadline
            ->add(new DateInterval("P{$daysUntilOrder}D"))
            ->setTime(0, 0, 0);
    }

    public function nextDeadline(int $dayOfWeek, int $placeId): DateTimeImmutable
    {
        $place = $this->getPlaces->oneById($placeId);
        $allowedDaysBefore = $this->getAllowedDaysBefore($place, $dayOfWeek) ?? 0;
        $allowedTime = $this->getAllowedTime($place, $dayOfWeek);

        // Výchozí čas deadline je 23:59:59, pokud není specifikován
        $deadlineHour = 23;
        $deadlineMinute = 59;
        $deadlineSecond = 59;
        if ($allowedTime !== null) {
            $deadlineHour = $allowedTime->h;
            $deadlineMinute = $allowedTime->i;
            $deadlineSecond = $allowedTime->s;
        }

        $now = $this->clock->now()->setTimezone(new DateTimezone('Europe/Prague'));
        $today = (int) $now->format('N');

        // Určíme, který den v týdnu je deadline (1 = pondělí … 7 = neděle)
        $deadlineDay = ($dayOfWeek - $allowedDaysBefore + 7) % 7;
        if ($deadlineDay === 0) {
            $deadlineDay = 7;
        }

        // Kolik dní zbývá do nejbližšího deadlineDay
        $daysUntil = ($deadlineDay - $today + 7) % 7;
        if ($daysUntil === 0) {
            // Dnes je deadline – podíváme se, jestli už jsme po deadline času
            $deadlineTimeToday = $now->setTime($deadlineHour, $deadlineMinute, $deadlineSecond);
            if ($now > $deadlineTimeToday) {
                // už máme prošlý dnešní deadline → počítáme další týden
                $daysUntil = 7;
            }
        }

        // Vracíme datum deadline v zadaném čase
        return $now
            ->add(new DateInterval("P{$daysUntil}D"))
            ->setTime($deadlineHour, $deadlineMinute, $deadlineSecond);
    }

    /**
     * Returns the deadline for the current ordering period (the upcoming pickup).
     * Unlike nextDeadline(), this can return a past datetime if the deadline has already passed.
     */
    public function deadlineForCurrentPeriod(int $dayOfWeek, int $placeId): DateTimeImmutable
    {
        $place = $this->getPlaces->oneById($placeId);
        $allowedDaysBefore = $this->getAllowedDaysBefore($place, $dayOfWeek) ?? 0;
        $allowedTime = $this->getAllowedTime($place, $dayOfWeek);

        $deadlineHour = 23;
        $deadlineMinute = 59;
        $deadlineSecond = 59;
        if ($allowedTime !== null) {
            $deadlineHour = $allowedTime->h;
            $deadlineMinute = $allowedTime->i;
            $deadlineSecond = $allowedTime->s;
        }

        $now = $this->clock->now()->setTimezone(new DateTimezone('Europe/Prague'));

        // Find the next occurrence of dayOfWeek (pickup day)
        $currentDow = (int) $now->format('N');
        $daysUntilPickup = ($dayOfWeek - $currentDow + 7) % 7;
        if ($daysUntilPickup === 0) {
            $daysUntilPickup = 7;
        }

        $nextPickupDate = $now->add(new DateInterval("P{$daysUntilPickup}D"));

        // Calculate the deadline for that pickup (X days before)
        $deadlineDate = $nextPickupDate->sub(new DateInterval("P{$allowedDaysBefore}D"));

        return $deadlineDate->setTime($deadlineHour, $deadlineMinute, $deadlineSecond);
    }

    private function getAllowedDaysBefore(Place $place, int $dayOfWeek): ?int
    {
        return match ($dayOfWeek) {
            1 => $place->day1AllowedDaysBefore,
            2 => $place->day2AllowedDaysBefore,
            3 => $place->day3AllowedDaysBefore,
            4 => $place->day4AllowedDaysBefore,
            5 => $place->day5AllowedDaysBefore,
            6 => $place->day6AllowedDaysBefore,
            7 => $place->day7AllowedDaysBefore,
            default => null,
        };
    }

    private function getAllowedTime(Place $place, int $dayOfWeek): ?DateInterval
    {
        return match ($dayOfWeek) {
            1 => $place->day1AllowedTime,
            2 => $place->day2AllowedTime,
            3 => $place->day3AllowedTime,
            4 => $place->day4AllowedTime,
            5 => $place->day5AllowedTime,
            6 => $place->day6AllowedTime,
            7 => $place->day7AllowedTime,
            default => null,
        };
    }
}
