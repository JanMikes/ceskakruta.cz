<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

final class CoolBalikDelivery
{
    public const DELIVERY_PLACE_ID = 21;

    /**
     * @return array<int, int|null>
     */
    public function getAllowedDaysForPostalCode(string $postalCode): array
    {
        return self::$mapping[(int) $postalCode] ?? [];
    }

    public function canDeliverToPostalCode(string $postalCode): bool
    {
        return isset(self::$mapping[(int) $postalCode]);
    }

    /**
     * @var array<int, array<int>>
     */
    private static $mapping = [
        10000 => [1, 2, 3, 4, 5, 6, 7],
    ];
}
