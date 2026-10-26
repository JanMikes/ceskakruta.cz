<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Recipe;

use CeskaKruta\Web\FormData\Admin\RecipeFormData;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly final class CreateRecipe
{
    public function __construct(
        public int $adminId,
        public int $productId,
        public null|string $name,
        public null|string $text,
        public null|UploadedFile $photo,
    ) {
    }

    public static function fromFormData(int $adminId, int $productId, RecipeFormData $formData): self
    {
        return new self(
            adminId: $adminId,
            productId: $productId,
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
            'product_id' => $this->productId,
            'name' => $this->name,
            'text' => $this->text,
        ];
    }
}
