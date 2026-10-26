<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * "Days before" hint of the place form / list (PLAN §2.15), ported from old `www/js/backend/managePlace.js`
 * and `app/components/PlacesList/default.latte`: resulting day = deliveryDay − daysBefore, wrapped into 1–7 (1 = Monday).
 */
final class PlaceDayHint
{
    private const DAY_NAMES = ['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota', 'Neděle'];

    /**
     * Weekday (1 = Monday … 7 = Sunday) on which ordering closes for delivery on `$deliveryDay`.
     *
     * @return int<1, 7>
     * @throws \InvalidArgumentException when $deliveryDay is not 1–7 or $daysBefore is negative
     */
    public static function orderDay(int $deliveryDay, int $daysBefore): int
    {
        self::assertDay($deliveryDay);

        if ($daysBefore < 0) {
            throw new \InvalidArgumentException(sprintf('Days before must not be negative, %d given.', $daysBefore));
        }

        $day = ($deliveryDay - $daysBefore) % 7;

        return $day <= 0 ? $day + 7 : $day;
    }

    /**
     * Czech weekday name as shown by the old admin: Pondělí, Úterý, Středa, Čtvrtek, Pátek, Sobota, Neděle.
     *
     * @throws \InvalidArgumentException when $day is not 1–7
     */
    public static function dayName(int $day): string
    {
        self::assertDay($day);

        return self::DAY_NAMES[$day - 1];
    }

    /**
     * @phpstan-assert int<1, 7> $day
     */
    private static function assertDay(int $day): void
    {
        if ($day < 1 || $day > 7) {
            throw new \InvalidArgumentException(sprintf('Day must be 1–7, %d given.', $day));
        }
    }
}
