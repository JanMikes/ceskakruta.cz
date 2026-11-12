<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin\Order;

/**
 * Result of `EditOrder` for the flash messages / redirect.
 */
readonly final class OrderEditResult
{
    public function __construct(
        public BoxxiEditOutcome $boxxi,
        /** Already sent to Pohoda – the change is not propagated there (out of scope), must be done by hand */
        public bool $inPohoda,
        /** The note changed on an order with a shipment – Boxxi cannot update the note */
        public bool $noteNotInBoxxi,
        /** null = not requested */
        public null|bool $emailSent,
    ) {
    }
}
