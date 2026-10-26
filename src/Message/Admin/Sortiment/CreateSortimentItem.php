<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Sortiment;

use CeskaKruta\Web\FormData\Admin\SortimentFormData;

readonly final class CreateSortimentItem
{
    public function __construct(
        public int $adminId,
        public int $categoryId,
        public null|string $name,
        public null|string $text,
    ) {
    }

    public static function fromFormData(int $adminId, int $categoryId, SortimentFormData $formData): self
    {
        return new self(
            adminId: $adminId,
            categoryId: $categoryId,
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
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'text' => $this->text,
        ];
    }
}
