<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class AliveCalendar
{
    public function __construct(
        public null|int $weeks,
        public null|string $weight,
        public bool $doubravaAvailable,
        public bool $rychvaldAvailable,
        public null|string $priceMix,
        public null|string $priceMan,
        public null|string $priceWoman,
        public null|string $note,
    ) {
    }
}
