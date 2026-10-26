<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminCalendarCold
{
    public function __construct(
        public int $id,
        public null|int $productTpId,
        public null|string $productName,
        public int $weekNumber,
        public int $year,
        public null|float $weightFrom,
        public null|float $weightTo,
        public bool $active,
    ) {
    }

    public function weekLabel(): string
    {
        return IsoWeekRange::format($this->weekNumber, $this->year);
    }

    /**
     * Old list: "{weight_from} až {weight_to}".
     */
    public function weightLabel(): string
    {
        return sprintf('%s až %s', $this->weightFrom, $this->weightTo);
    }
}
