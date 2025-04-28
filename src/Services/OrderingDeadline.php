<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Query\GetPlaces;
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
            ->add(new \DateInterval("P{$daysUntilOrder}D"))
            ->setTime(0, 0, 0);
    }

    public function nextDeadline(int $dayOfWeek, int $placeId): DateTimeImmutable
    {
        $place = $this->getPlaces->oneById($placeId);
        $allowedDaysBefore = $place->{'day'.$dayOfWeek.'AllowedDaysBefore'} ?? 0;

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
            // Dnes je deadline – podíváme se, jestli už jsme po 23:59:59
            $deadlineTimeToday = $now->setTime(23, 59, 59);
            if ($now > $deadlineTimeToday) {
                // už máme prošlý dnešní deadline → počítáme další týden
                $daysUntil = 7;
            }
        }

        // Vracíme datum deadline ve 23:59:59
        return $now
            ->add(new \DateInterval("P{$daysUntil}D"))
            ->setTime(23, 59, 59);
    }
}
