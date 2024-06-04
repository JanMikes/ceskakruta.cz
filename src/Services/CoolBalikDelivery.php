<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

final class CoolBalikDelivery
{
    public const DELIVERY_PLACE_ID = 15;

    /**
     * @return array<int, int|null>
     */
    public function getAllowedDaysBeforeForPostalCode(string $postalCode): array
    {
        $mapping = self::$mapping[$postalCode] ?? [];

        return [
            1 => in_array(1, $mapping, true) ? 1 : null,
            2 => in_array(2, $mapping, true) ? 1 : null,
            3 => in_array(3, $mapping, true) ? 1 : null,
            4 => in_array(4, $mapping, true) ? 1 : null,
            5 => in_array(5, $mapping, true) ? 1 : null,
            6 => in_array(6, $mapping, true) ? 1 : null,
            7 => in_array(7, $mapping, true) ? 1 : null,
        ];
    }

    public function canDeliverToPostalCode(string $postalCode): bool
    {
        return isset(self::$mapping[$postalCode]);
    }

    /**
     * @var array<string|int, array<int>>
     */
    private static $mapping = [
        '10000' => [1, 2, 3, 4, 5, 6, 7],
    ];
}
