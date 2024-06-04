<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Place
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isDelivery,
        public bool $isOwnDelivery,
        public bool $forcePacking,
        public null|int $day1AllowedDaysBefore,
        public null|int $day2AllowedDaysBefore,
        public null|int $day3AllowedDaysBefore,
        public null|int $day4AllowedDaysBefore,
        public null|int $day5AllowedDaysBefore,
        public null|int $day6AllowedDaysBefore,
        public null|int $day7AllowedDaysBefore,
        public null|string $address1,
        public null|string $address2,
        public null|string $address3,
        public null|string $mapUrl,
        public null|string $phone,
        public null|string $openingHours,
    ) {
    }
}
