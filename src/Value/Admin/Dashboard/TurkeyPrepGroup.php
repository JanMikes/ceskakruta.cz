<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Whole and half turkeys of one type (krůta / krocan) to prepare for a day.
 */
readonly final class TurkeyPrepGroup
{
    /**
     * @param list<PrepItem> $items whole product first, then its half, by weight class
     */
    public function __construct(
        public int $type,
        public string $label,
        public array $items,
    ) {
    }

    /**
     * Birds needed: whole pieces + halves / 2.
     */
    public function wholePieces(): float
    {
        $pieces = 0.0;

        foreach ($this->items as $item) {
            $pieces += $item->isHalf() ? $item->amount / 2 : $item->amount;
        }

        return $pieces;
    }
}
