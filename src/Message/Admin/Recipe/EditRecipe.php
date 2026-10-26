<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Recipe;

use CeskaKruta\Web\FormData\Admin\RecipeFormData;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly final class EditRecipe
{
    public function __construct(
        public int $id,
        public int $adminId,
        public null|string $name,
        public null|string $text,
        /** Replaces the current photo, null keeps it */
        public null|UploadedFile $photo,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, RecipeFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            name: $formData->name,
            text: $formData->text,
            photo: $formData->image,
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
