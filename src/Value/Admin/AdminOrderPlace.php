<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Place of an order as needed by the Pohoda / Boxxi integrations (`place` row).
 */
readonly final class AdminOrderPlace
{
    public function __construct(
        public int $id,
        public string $name,
        /** `place.is_cool_balik` (NULL in DB = false) */
        public bool $isCoolBalik,
        /** `place.force_delivery_address` = delivery place ("Rozvoz") */
        public bool $forceDeliveryAddress,
    ) {
    }
}
