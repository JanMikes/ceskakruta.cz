<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

use DateTimeImmutable;

readonly final class AdminUserListItem
{
    public function __construct(
        public int $id,
        public DateTimeImmutable $registeredAt,
        public null|string $email,
        public null|string $name,
        public int $ordersCount,
        public int $bannedProductsCount,
        public bool $allowWholesale,
        public bool $isAdmin,
    ) {
    }
}
