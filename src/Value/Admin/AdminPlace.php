<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminPlace
{
    /**
     * @param array<int<1, 7>, AdminPlaceDay> $days
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $title,
        public bool $forceDeliveryAddress,
        public bool $isCoolBalik,
        public bool $forcePacking,
        public bool $allowOnlyWholesale,
        public null|string $description,
        public null|string $openingHours,
        public null|string $openingHoursEmail,
        public array $days,
        public bool $active,
    ) {
    }
}
