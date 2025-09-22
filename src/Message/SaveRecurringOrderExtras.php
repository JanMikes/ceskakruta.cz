<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class SaveRecurringOrderExtras
{
    /**
     * @param array<int, array{amount: array<string, string>, note: null|string, is_sliced?: bool, is_packed?: bool}> $items
     */
    public function __construct(
        public int $userId,
        public int $dayOfWeek,
        public array $items,
    ) {
    }
}