<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Place;

use CeskaKruta\Web\FormData\Admin\PlaceFormData;

readonly final class CreatePlace
{
    public function __construct(
        public int $adminId,
        public PlaceColumns $columns,
        public null|int $pricesFromPlaceId,
    ) {
    }

    public static function fromFormData(int $adminId, PlaceFormData $formData): self
    {
        return new self(
            adminId: $adminId,
            columns: PlaceColumns::fromFormData($formData),
            pricesFromPlaceId: $formData->pricesFromPlaceId,
        );
    }
}
