<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminCalendarDead;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `ManageCalendarDeadFormFactory`.
 */
final class CalendarDeadFormData
{
    use CalendarWeekFields;

    public bool $farmaDoubrava = false;

    public bool $farmaRychvald = false;

    #[Assert\Regex(pattern: '/^\d+$/', message: 'Stáří musí být celé číslo!')]
    public null|string $weekAge = null;

    #[Assert\Length(max: 20)]
    public null|string $weight = null;

    #[Assert\Length(max: 20)]
    public null|string $priceMix = null;

    #[Assert\Length(max: 20)]
    public null|string $priceMan = null;

    #[Assert\Length(max: 20)]
    public null|string $priceWoman = null;

    #[Assert\Length(max: 40)]
    public null|string $note = null;

    public function __construct()
    {
        $this->year = date('Y');
    }

    public static function fromCalendarDead(AdminCalendarDead $row): self
    {
        $formData = new self();
        $formData->farmaDoubrava = $row->farmaDoubrava;
        $formData->farmaRychvald = $row->farmaRychvald;
        $formData->weekNumber = (string) $row->weekNumber;
        $formData->year = (string) $row->year;
        $formData->weekAge = $row->weekAge === null ? null : (string) $row->weekAge;
        $formData->weight = $row->weight;
        $formData->priceMix = $row->priceMix;
        $formData->priceMan = $row->priceMan;
        $formData->priceWoman = $row->priceWoman;
        $formData->note = $row->note;

        return $formData;
    }
}
