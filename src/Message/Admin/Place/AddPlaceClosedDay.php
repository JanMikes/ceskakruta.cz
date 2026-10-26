<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Place;

use CeskaKruta\Web\FormData\Admin\PlaceClosedDayFormData;
use DateTimeImmutable;

readonly final class AddPlaceClosedDay
{
    public function __construct(
        public int $adminId,
        public DateTimeImmutable $date,
        /** null = all places */
        public null|int $placeId,
    ) {
    }

    public static function fromFormData(int $adminId, PlaceClosedDayFormData $formData): self
    {
        assert($formData->date !== null);

        return new self(
            adminId: $adminId,
            date: $formData->date,
            placeId: $formData->placeId,
        );
    }
}
