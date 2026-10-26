<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final class PlaceClosedDayFormData
{
    #[Assert\NotNull(message: 'Datum je povinný údaj!')]
    public null|DateTimeImmutable $date = null;

    /** null = all places */
    public null|int $placeId = null;
}
