<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminActuality;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageActualityFormFactory`: date and name (max 50) required, texts are WYSIWYG.
 */
final class ActualityFormData
{
    #[Assert\NotNull(message: 'Datum je povinný údaj!')]
    public null|\DateTimeImmutable $date = null;

    #[Assert\NotBlank(message: 'Nadpis je povinný!')]
    #[Assert\Length(max: 50)]
    public null|string $name = null;

    public null|string $text = null;

    public null|string $textFull = null;

    public static function fromActuality(AdminActuality $actuality): self
    {
        $formData = new self();
        $formData->date = $actuality->date;
        $formData->name = $actuality->name;
        $formData->text = $actuality->text;
        $formData->textFull = $actuality->textFull;

        return $formData;
    }
}
