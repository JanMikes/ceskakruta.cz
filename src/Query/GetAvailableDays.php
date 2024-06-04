<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use DateTimeImmutable;

readonly final class GetAvailableDays
{
    public function __construct(
        private GetPlaces $getPlaces,
        private CartStorage $cartStorage,
        private GetPlaceClosedDays $getPlaceClosedDays,
        private CeskaKrutaDelivery $ceskaKrutaDelivery,
        private CoolBalikDelivery $coolBalikDelivery,
    ) {
    }

    /**
     * @return array<DateTimeImmutable>
     */
    public function forPlace(int $placeId): array
    {
        $availableDays = [];
        $date = new DateTimeImmutable(); // začneme dnešním dnem

        for ($i=0; $i<=365; $i++) {

            if ($this->isDateAvailable($date, $placeId)) {
                $availableDays[] = $date;
            }

            $date = $date->modify('+1 day');
        }

        return $availableDays;
    }

    public function isDateAvailable(DateTimeImmutable $date, int $placeId): bool
    {
        $place = $this->getPlaces->oneById($placeId);
        $lockedWeek = $this->cartStorage->getLockedWeek();

        if ($lockedWeek !== null) {
            if ($lockedWeek->year !== (int) $date->format('Y') || $lockedWeek->number !== (int) $date->format('W')) {
                return false;
            }
        }

        $today = new DateTimeImmutable();
        $weekDay = (int) $date->format('w'); // 0 = neděle, 1 = pondělí, ..., 6 = sobota
        $weekDay = $weekDay === 0 ? 7 : $weekDay; // Převedeme neděli na 7

        // Zabalíme všechny proměnné pro dny do pole
        $allowDaysBefore = [
            1 => $place->day1AllowedDaysBefore,
            2 => $place->day2AllowedDaysBefore,
            3 => $place->day3AllowedDaysBefore,
            4 => $place->day4AllowedDaysBefore,
            5 => $place->day5AllowedDaysBefore,
            6 => $place->day6AllowedDaysBefore,
            7 => $place->day7AllowedDaysBefore,
        ];

        $postalCode = $this->cartStorage->getDeliveryAddress()?->postalCode;

        if ($postalCode !== null && $placeId === $this->ceskaKrutaDelivery::DELIVERY_PLACE_ID) {
            $allowDaysBefore = $this->ceskaKrutaDelivery->getAllowedDaysBeforeForPostalCode($postalCode);
        }

        if ($postalCode !== null && $placeId === $this->coolBalikDelivery::DELIVERY_PLACE_ID) {
            $allowDaysBefore = $this->coolBalikDelivery->getAllowedDaysBeforeForPostalCode($postalCode);
        }

        $daysBefore = $allowDaysBefore[$weekDay] ?? null;

        if ($daysBefore === null) {
            return false;
        }

        $skipDates = $this->getPlaceClosedDays->forPlace($placeId);

        if (in_array($date->format('Y-m-d'), $skipDates, true)) {
            return false;
        }

        // Zkontrolujeme, zda je datum dostatečně daleko od dnešního dne
        return $date->diff($today)->days >= $daysBefore;
    }
}
