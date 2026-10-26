<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminCalendarDead
{
    public function __construct(
        public int $id,
        public bool $farmaDoubrava,
        public bool $farmaRychvald,
        public int $weekNumber,
        public int $year,
        public null|int $weekAge,
        public null|string $weight,
        public null|string $priceMix,
        public null|string $priceMan,
        public null|string $priceWoman,
        public null|string $note,
        public bool $active,
    ) {
    }

    public function weekLabel(): string
    {
        return IsoWeekRange::format($this->weekNumber, $this->year);
    }
}
