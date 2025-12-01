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
        public bool $allowOnlyWholesale,
        public null|int $day1AllowedDaysBefore,
        public null|int $day2AllowedDaysBefore,
        public null|int $day3AllowedDaysBefore,
        public null|int $day4AllowedDaysBefore,
        public null|int $day5AllowedDaysBefore,
        public null|int $day6AllowedDaysBefore,
        public null|int $day7AllowedDaysBefore,
        public null|\DateInterval $day1AllowedTime,
        public null|\DateInterval $day2AllowedTime,
        public null|\DateInterval $day3AllowedTime,
        public null|\DateInterval $day4AllowedTime,
        public null|\DateInterval $day5AllowedTime,
        public null|\DateInterval $day6AllowedTime,
        public null|\DateInterval $day7AllowedTime,
        public null|string $address1,
        public null|string $address2,
        public null|string $address3,
        public null|string $mapUrl,
        public null|string $phone,
        public null|string $openingHours,
        public null|string $openingHoursEmail,
    ) {
    }
}
