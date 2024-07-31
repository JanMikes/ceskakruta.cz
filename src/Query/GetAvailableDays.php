<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Services\Cart\CartService;
use DateTimeImmutable;

readonly final class GetAvailableDays
{
    public function __construct(
        private CartService $cartService,
    ) {
    }

    /**
     * @return array<DateTimeImmutable>
     */
    public function forPlace(int $placeId): array
    {
        $availableDays = [];
        $date = new DateTimeImmutable(); // začneme dnešním dnem

        for ($i=0; $i<=184; $i++) {

            if ($this->cartService->isDateAvailable($date, $placeId)) {
                $availableDays[$date->format('Y-m-d')] = $date;
            }

            $date = $date->modify('+1 day');
        }

        return $availableDays;
    }
}
