<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class CopyRecurringOrderToDay
{
    public function __construct(
        public int $userId,
        public int $sourceDay,
        public int $targetDay,
    ) {
    }
}