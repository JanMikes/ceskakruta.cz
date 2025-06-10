<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

final class CeskaKrutaShopDelivery
{
    public const DELIVERY_PLACE_ID = 22;

    /**
     * @return array<int>
     */
    public function getAllowedDaysForPostalCode(string $postalCode): array
    {
        return [1, 2, 3, 4, 5];
    }

    public function canDeliverToPostalCode(string $postalCode): bool
    {
        return true;
    }
}
