<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Week
{
    public function __construct(
        public int $number,
        public int $year,
    ) {
    }
}
