<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Dashboard;

/**
 * Order expected from a recurring order (`recurring_order` = user + weekday) for one delivery day.
 */
readonly final class ExpectedOrder
{
    public function __construct(
        public \DateTimeImmutable $date,
        public int $userId,
        public string $customerName,
        public string $placeName,
        public bool $isWholesale,
        /** Ordering deadline of the user's preferred place for the day, null = the place does not deliver that weekday */
        public null|\DateTimeImmutable $deadline,
        public ExpectedOrderStatus $status,
        public null|int $orderId = null,
        /** Why the order is (or will be) missing */
        public null|string $problem = null,
    ) {
    }
}
