<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

readonly final class DeliveryService
{
    public const CESKA_KRUTA_DELIVERY_PLACE_ID = 8;
    public const COOLBALIK_DELIVERY_PLACE_ID = 15;

    // TODO: get delivery days to offer

    public function canCeskaKrutaDeliverToPostalCode(string $postalCode): bool
    {
        // 7374*
        if (str_starts_with($postalCode, '7374')) {
            return true;
        }

        return false;
    }

    public function canCoolbalikDeliverToPostalCode(string $postalCode): bool
    {
        return true;
    }
}
