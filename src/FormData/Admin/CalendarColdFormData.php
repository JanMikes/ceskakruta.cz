<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminCalendarCold;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageCalendarColdFormFactory`.
 */
final class CalendarColdFormData
{
    use CalendarWeekFields;

    #[Assert\NotNull(message: 'Produkt je povinný!')]
    public null|int $productTpId = null;

    #[Assert\NotNull(message: 'Váha od je povinná!')]
    #[Assert\PositiveOrZero]
    public null|float $weightFrom = null;

    #[Assert\NotNull(message: 'Váha do je povinná!')]
    #[Assert\PositiveOrZero]
    public null|float $weightTo = null;

    public function __construct()
    {
        $this->year = date('Y');
    }

    public static function fromCalendarCold(AdminCalendarCold $row): self
    {
        $formData = new self();
        $formData->productTpId = $row->productTpId;
        $formData->weekNumber = (string) $row->weekNumber;
        $formData->year = (string) $row->year;
        $formData->weightFrom = $row->weightFrom;
        $formData->weightTo = $row->weightTo;

        return $formData;
    }
}
