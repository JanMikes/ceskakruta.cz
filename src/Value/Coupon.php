<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use DateTimeImmutable;

readonly final class Coupon
{
    public function __construct(
        public int $id,
        public string $code,
        public null|int $usageLimit,
        public null|DateTimeImmutable $untilDate,
        public null|DateTimeImmutable $deliveryUntilDate,
        public null|int $percentValue,
    ) {
    }
}
