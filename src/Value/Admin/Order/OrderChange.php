<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Order;

/**
 * One row of "Co se změnilo": what it is, how it was and how it is now ('–' = did not exist / removed).
 */
readonly final class OrderChange
{
    public function __construct(
        public string $label,
        public string $before,
        public string $after,
    ) {
    }
}
