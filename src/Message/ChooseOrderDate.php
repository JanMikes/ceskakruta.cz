<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

use DateTimeImmutable;

readonly final class ChooseOrderDate
{
    public function __construct(
        public int $placeId,
        public DateTimeImmutable $date,
    ) {
    }
}
