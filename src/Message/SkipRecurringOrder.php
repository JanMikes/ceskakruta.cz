<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class SkipRecurringOrder
{
    public function __construct(
        public int $userId,
        public int $dayOfWeek,
    ) {
    }
}