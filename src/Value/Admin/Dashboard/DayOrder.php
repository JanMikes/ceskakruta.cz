<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Order row of the selected day in the dashboard operations part.
 */
readonly final class DayOrder
{
    public function __construct(
        public int $id,
        public string $name,
        public string $placeName,
        public SalesChannel $channel,
        public \DateTimeImmutable $customerDate,
        public \DateTimeImmutable $expeditionDate,
        public float $priceTotal,
        public bool $hasNote,
        public bool $pohodaSynced,
        /** null = not a CoolBalík order, Boxxi does not apply */
        public null|bool $boxxiSynced,
    ) {
    }
}
