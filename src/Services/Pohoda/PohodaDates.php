<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Pohoda;

use CeskaKruta\Web\Value\Admin\AdminOrderPlace;

/**
 * Pohoda order dates, ported 1:1 from old `libs/services/Pohoda.php` (`calculateDateTo`, `calculateDateFrom`, split rule
 * in `sendOrderRequest`). Pure, no dependencies (unit tested in tests/Unit/Pohoda/PohodaDatesTest).
 */
final class PohodaDates
{
    /**
     * `ord:dateTo` = expedition date ("datum uzavírky / expedice"), derived from the order date (`order.date`):
     * CoolBalík places shift to the previous expedition day, shops other than place 1 without delivery shift Tue/Wed → Mon
     * and Fri → Thu, everything else keeps the order date.
     */
    public function dateTo(\DateTimeImmutable $orderDate, AdminOrderPlace $place): \DateTimeImmutable
    {
        $dayOfWeek = (int) $orderDate->format('N');

        if ($place->isCoolBalik) {
            return match ($dayOfWeek) {
                1 => $orderDate->modify('previous friday'),
                2 => $orderDate->modify('previous monday'),
                3, 4 => $orderDate->modify('previous tuesday'),
                5, 6 => $orderDate->modify('previous thursday'),
                default => $orderDate->modify('last tuesday'),
            };
        }

        // Shops except Rychvald
        if ($place->id !== 1 && !$place->forceDeliveryAddress) {
            return match ($dayOfWeek) {
                2, 3 => $orderDate->modify('previous monday'),
                5 => $orderDate->modify('previous thursday'),
                default => $orderDate,
            };
        }

        return $orderDate;
    }

    /**
     * `ord:dateFrom` = slaughter date ("datum porážky"), derived from the expedition date (`dateTo`).
     *
     * @param bool $wholeTurkeysOrder true only for the split CoolBalík order `WEB{id}K` (items with `weight_from`)
     */
    public function dateFrom(\DateTimeImmutable $dateTo, AdminOrderPlace $place, bool $wholeTurkeysOrder): \DateTimeImmutable
    {
        $dayOfWeek = (int) $dateTo->format('N');

        if ($place->isCoolBalik && $dayOfWeek <= 5) {
            return match ($dayOfWeek) {
                2 => $dateTo->modify($wholeTurkeysOrder ? 'previous monday' : 'previous friday'),
                4 => $dateTo->modify($wholeTurkeysOrder ? 'previous tuesday' : 'previous monday'),
                5 => $dateTo->modify('previous monday'),
                default => $dateTo->modify('previous friday'),
            };
        }

        return match ($dayOfWeek) {
            3 => $dateTo->modify('previous monday'),
            4, 5 => $dateTo->modify('previous tuesday'),
            default => $dateTo->modify('previous friday'),
        };
    }

    /**
     * Whole turkeys go into a separate order `WEB{id}K` for CoolBalík places when the expedition is on Tuesday or Thursday.
     */
    public function splitsWholeTurkeys(\DateTimeImmutable $dateTo, AdminOrderPlace $place): bool
    {
        return $place->isCoolBalik && in_array((int) $dateTo->format('N'), [2, 4], true);
    }
}
