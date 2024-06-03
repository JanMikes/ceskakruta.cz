<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use DateTimeImmutable;

readonly final class Order
{
    public function __construct(
        public int $id,
        public int $placeId,
        public DateTimeImmutable $orderedAt,
        public DateTimeImmutable $date,
        public string $email,
        public string $phone,
        public string $name,
        public bool $payByCard,
        public null|string $deliveryStreet,
        public null|string $deliveryCity,
        public null|string $deliveryPostalCode,
        public null|string $note,
        public float $priceTotal,
    ) {
    }
}
