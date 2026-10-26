<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Sortiment;

use CeskaKruta\Web\FormData\Admin\SortimentFormData;

readonly final class EditSortimentItem
{
    public function __construct(
        public int $id,
        public int $adminId,
        public null|string $name,
        public null|string $text,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, SortimentFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            name: $formData->name,
            text: $formData->text,
        );
    }

    /**
     * @return array<string, null|scalar>
     */
    public function columns(): array
    {
        return [
            'name' => $this->name,
            'text' => $this->text,
        ];
    }
}
