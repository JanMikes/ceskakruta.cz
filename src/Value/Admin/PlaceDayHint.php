<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * "Days before" hint of the place form / list (PLAN §2.15), ported from old `www/js/backend/managePlace.js`
 * and `app/components/PlacesList/default.latte`: resulting day = deliveryDay − daysBefore, wrapped into 1–7 (1 = Monday).
 */
final class PlaceDayHint
{
    /**
     * Weekday (1 = Monday … 7 = Sunday) on which ordering closes for delivery on `$deliveryDay`.
     *
     * @return int<1, 7>
     * @throws \InvalidArgumentException when $deliveryDay is not 1–7 or $daysBefore is negative
     */
    public static function orderDay(int $deliveryDay, int $daysBefore): int
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * Czech weekday name as shown by the old admin: Pondělí, Úterý, Středa, Čtvrtek, Pátek, Sobota, Neděle.
     *
     * @throws \InvalidArgumentException when $day is not 1–7
     */
    public static function dayName(int $day): string
    {
        throw new \LogicException('Not implemented yet');
    }
}
