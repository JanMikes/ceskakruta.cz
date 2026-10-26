<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Place;

use CeskaKruta\Web\FormData\Admin\PlaceFormData;

readonly final class EditPlace
{
    public function __construct(
        public int $id,
        public int $adminId,
        public PlaceColumns $columns,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, PlaceFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            columns: PlaceColumns::fromFormData($formData),
        );
    }
}
