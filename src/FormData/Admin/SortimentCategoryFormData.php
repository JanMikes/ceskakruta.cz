<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\SortimentCategory;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Sortiment category: only the name (`sortiment_category.name` varchar 255), required.
 */
final class SortimentCategoryFormData
{
    #[Assert\NotBlank(message: 'Název je povinný!')]
    #[Assert\Length(max: 255)]
    public null|string $name = null;

    public static function fromCategory(SortimentCategory $category): self
    {
        $formData = new self();
        $formData->name = $category->name;

        return $formData;
    }
}
