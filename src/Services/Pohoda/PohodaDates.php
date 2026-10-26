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
        throw new \LogicException('Not implemented yet');
    }

    /**
     * `ord:dateFrom` = slaughter date ("datum porážky"), derived from the expedition date (`dateTo`).
     *
     * @param bool $wholeTurkeysOrder true only for the split CoolBalík order `WEB{id}K` (items with `weight_from`)
     */
    public function dateFrom(\DateTimeImmutable $dateTo, AdminOrderPlace $place, bool $wholeTurkeysOrder): \DateTimeImmutable
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * Whole turkeys go into a separate order `WEB{id}K` for CoolBalík places when the expedition is on Tuesday or Thursday.
     */
    public function splitsWholeTurkeys(\DateTimeImmutable $dateTo, AdminOrderPlace $place): bool
    {
        throw new \LogicException('Not implemented yet');
    }
}
