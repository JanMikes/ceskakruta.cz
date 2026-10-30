<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Admin\Dashboard;

use CeskaKruta\Web\Query\Admin\GetRecurringOrdersOverview;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\Admin\Dashboard\ExpectedOrder;
use CeskaKruta\Web\Value\Admin\Dashboard\ExpectedOrderStatus;
use CeskaKruta\Web\Value\Place;

/**
 * Checks that recurring orders turned into real orders. The cron (`/cron/recurring-order-trigger`) creates the order only
 * in the 24 hours after the ordering deadline of the user's preferred place and silently skips users without a delivery
 * address – such orders are never created and nothing else reports it.
 */
readonly final class RecurringOrdersWatch
{
    /** After the deadline the order is reported missing only after this, the cron needs a run to create it */
    public const CRON_GRACE_MINUTES = 60;

    /** The cron creates the order only within this window after the deadline */
    private const CRON_WINDOW_HOURS = 24;

    public function __construct(
        private GetRecurringOrdersOverview $overview,
        private GetPlaces $getPlaces,
    ) {
    }

    /**
     * Expected orders for delivery days `$from`–`$to`, by day, then customer name.
     *
     * @return list<ExpectedOrder>
     */
    public function expectedOrders(\DateTimeImmutable $now, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $recurringOrders = $this->overview->recurringOrders();

        if ($recurringOrders === []) {
            return [];
        }

        $places = $this->getPlaces->all();
        $skipped = $this->overview->skippedDays($from, $to);
        $existing = $this->overview->existingOrders(array_values(array_unique(array_column($recurringOrders, 'user_id'))), $from, $to);
        $expected = [];

        for ($day = $from->setTime(0, 0); $day <= $to; $day = $day->modify('+1 day')) {
            $dayOfWeek = (int) $day->format('N');
            $key = $day->format('Y-m-d');

            foreach ($recurringOrders as $recurringOrder) {
                if ($recurringOrder['day_of_week'] !== $dayOfWeek) {
                    continue;
                }

                $place = $places[$recurringOrder['preferred_place_id'] ?? 0] ?? null;
                $deadline = $place !== null ? self::deadline($place, $day) : null;
                $userKey = $recurringOrder['user_id'] . '_' . $key;

                [$status, $problem] = match (true) {
                    isset($existing[$userKey]) => [ExpectedOrderStatus::Created, null],
                    isset($skipped[$userKey]) => [ExpectedOrderStatus::Skipped, null],
                    default => self::pendingStatus($now, $deadline, $place, $recurringOrder['has_delivery_address']),
                };

                $expected[] = new ExpectedOrder(
                    date: $day,
                    userId: $recurringOrder['user_id'],
                    customerName: $recurringOrder['name'],
                    placeName: $place->name ?? '–',
                    isWholesale: $recurringOrder['is_wholesale'],
                    deadline: $deadline,
                    status: $status,
                    orderId: $existing[$userKey] ?? null,
                    problem: $problem,
                );
            }
        }

        return $expected;
    }

    /**
     * Ordering deadline for a delivery day = `days_before` days earlier at the place's time (as `OrderingDeadline`).
     */
    public static function deadline(Place $place, \DateTimeImmutable $day): null|\DateTimeImmutable
    {
        [$daysBefore, $time] = match ((int) $day->format('N')) {
            1 => [$place->day1AllowedDaysBefore, $place->day1AllowedTime],
            2 => [$place->day2AllowedDaysBefore, $place->day2AllowedTime],
            3 => [$place->day3AllowedDaysBefore, $place->day3AllowedTime],
            4 => [$place->day4AllowedDaysBefore, $place->day4AllowedTime],
            5 => [$place->day5AllowedDaysBefore, $place->day5AllowedTime],
            6 => [$place->day6AllowedDaysBefore, $place->day6AllowedTime],
            default => [$place->day7AllowedDaysBefore, $place->day7AllowedTime],
        };

        if ($daysBefore === null) {
            return null;
        }

        return $day
            ->modify(sprintf('-%d days', $daysBefore))
            ->setTime($time->h ?? 23, $time->i ?? 59, $time->s ?? 59);
    }

    /**
     * @return array{ExpectedOrderStatus, null|string}
     */
    private static function pendingStatus(\DateTimeImmutable $now, null|\DateTimeImmutable $deadline, null|Place $place, bool $hasDeliveryAddress): array
    {
        if ($place === null) {
            return [ExpectedOrderStatus::Missing, 'Zákazník nemá zvolené výdejní místo, cron objednávku nevytvoří.'];
        }

        if ($deadline === null) {
            return [ExpectedOrderStatus::Missing, sprintf('Místo „%s“ v tento den neobjednává, cron objednávku nevytvoří.', $place->name)];
        }

        $problem = $hasDeliveryAddress ? null : 'Zákazník nemá vyplněné jméno, telefon a doručovací adresu, cron objednávku přeskočí.';

        if ($now < $deadline) {
            return [ExpectedOrderStatus::Waiting, $problem];
        }

        if ($problem === null && $now < $deadline->modify(sprintf('+%d minutes', self::CRON_GRACE_MINUTES))) {
            return [ExpectedOrderStatus::Processing, null];
        }

        if ($problem === null) {
            $problem = $now < $deadline->modify(sprintf('+%d hours', self::CRON_WINDOW_HOURS))
                ? 'Uzávěrka proběhla, objednávka zatím nevznikla – zkontrolujte cron.'
                : 'Uzávěrka proběhla před více než 24 h, cron už objednávku nevytvoří – je potřeba ji vytvořit ručně.';
        }

        return [ExpectedOrderStatus::Missing, $problem];
    }
}
