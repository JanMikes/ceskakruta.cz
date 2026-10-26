<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminSortimentItem;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageSortimentFormFactory`: name required (max 50), text; the category comes from the URL.
 */
final class SortimentFormData
{
    #[Assert\NotBlank(message: 'Název je povinný!')]
    #[Assert\Length(max: 50)]
    public null|string $name = null;

    public null|string $text = null;

    public static function fromItem(AdminSortimentItem $item): self
    {
        $formData = new self();
        $formData->name = $item->name;
        $formData->text = $item->text;

        return $formData;
    }
}
