<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminVendor
{
    public function __construct(
        public int $id,
        public null|string $place,
        public null|string $name,
        public null|string $address,
        public null|string $city,
        public null|string $zip,
        public null|string $email,
        public null|string $web,
        public null|string $phone,
        public bool $active,
    ) {
    }
}
