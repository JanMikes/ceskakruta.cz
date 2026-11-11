<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class PlaceClosedDayFormData
{
    #[Assert\NotNull(message: 'Datum je povinný údaj!')]
    public null|DateTimeImmutable $date = null;

    /** null = all places */
    public null|int $placeId = null;

    /** false = zavřeno, true = otevřeno navíc */
    public bool $isOpen = false;

    /** Only for an extra opening day: how many days before the date the order must be placed */
    #[Assert\Range(min: 0, max: 30, notInRangeMessage: 'Počet dní musí být mezi {{ min }} a {{ max }}!')]
    public null|int $daysBefore = 1;

    #[Assert\Callback]
    public function validateDaysBefore(ExecutionContextInterface $context): void
    {
        if ($this->isOpen && $this->daysBefore === null) {
            $context->buildViolation('Zadejte, kolik dní předem je nutné objednat!')
                ->atPath('daysBefore')
                ->addViolation();
        }
    }
}
