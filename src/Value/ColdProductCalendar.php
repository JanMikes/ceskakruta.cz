<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class ColdProductCalendar
{
    public function __construct(
        public ColdProductType $type,
        public float $weightFrom,
        public float $weightTo,
    ) {
    }
}
