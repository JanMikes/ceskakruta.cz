<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

use DateTimeImmutable;

readonly final class AdminPlaceClosedDay
{
    public function __construct(
        public int $id,
        public DateTimeImmutable $date,
        /** null = closed for all places */
        public null|int $placeId,
        public null|string $placeName,
    ) {
    }
}
