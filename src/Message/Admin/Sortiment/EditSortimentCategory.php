<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Sortiment;

use CeskaKruta\Web\FormData\Admin\SortimentCategoryFormData;

readonly final class EditSortimentCategory
{
    public function __construct(
        public int $id,
        public int $adminId,
        public string $name,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, SortimentCategoryFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            name: (string) $formData->name,
        );
    }

    /**
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
