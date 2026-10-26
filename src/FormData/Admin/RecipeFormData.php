<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminRecipe;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageRecipeFormFactory`: name required (max 50), optional JPG/GIF/PNG photo, text.
 */
final class RecipeFormData
{
    #[Assert\NotBlank(message: 'Název je povinný!')]
    #[Assert\Length(max: 50)]
    public null|string $name = null;

    #[Assert\Image(
        maxSize: '10M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif'],
        mimeTypesMessage: 'Obrázek musí být ve formátu JPG, GIF nebo PNG!',
    )]
    public null|UploadedFile $image = null;

    public null|string $text = null;

    public static function fromRecipe(AdminRecipe $recipe): self
    {
        $formData = new self();
        $formData->name = $recipe->name;
        $formData->text = $recipe->text;

        return $formData;
    }
}
