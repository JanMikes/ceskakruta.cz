<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

use Ramsey\Uuid\UuidInterface;

readonly final class CreateOrderFromRecurringOrder
{
    public function __construct(
        public UuidInterface $recurringOrderId,
    ) {
    }
}
