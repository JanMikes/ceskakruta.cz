<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

use CeskaKruta\Web\Value\PackageAmount;

readonly final class SaveRecurringOrder
{
    public function __construct(
        public int $userId,
        public int $dayOfWeek,
        /** @var array<int, array{amount: array<string, string>, note: null|string, is_sliced?: bool, is_packed?: bool}> */
        public array $items,
    ) {
    }
}
