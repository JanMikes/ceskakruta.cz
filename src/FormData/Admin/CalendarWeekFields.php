<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Week + year of every calendar form (old ManageCalendar*FormFactory): text inputs validated as integers,
 * week 1–53, year 2014…current+2 (default current year).
 */
trait CalendarWeekFields
{
    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Týden je povinný!'),
        new Assert\Regex(pattern: '/^\d+$/', message: 'Týden musí být celé číslo!'),
        new Assert\Range(notInRangeMessage: 'Týden musí být v rozmezí od 1 do 53!', min: 1, max: 53),
    ])]
    public null|string $weekNumber = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Rok je povinný!'),
        new Assert\Regex(pattern: '/^\d+$/', message: 'Rok musí být celé číslo!'),
        new Assert\Range(notInRangeMessage: 'Rok musí být v rozmezí od {{ min }} do {{ max }}!', min: 2014, maxPropertyPath: 'maxYear'),
    ])]
    public null|string $year = null;

    public function getMaxYear(): int
    {
        return (int) date('Y') + 2;
    }
}
