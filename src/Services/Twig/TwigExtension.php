<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Twig;

use DateTimeImmutable;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TwigExtension extends AbstractExtension
{
    /**
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('dayOfWeek', [$this, 'dayOfWeek']),
        ];
    }


    public function dayOfWeek(DateTimeImmutable $date): string
    {
        $dayOfWeek = (int) $date->format('w');

        return match($dayOfWeek) {
            0 => 'Neděle',
            1 => 'Pondělí',
            2 => 'Úterý',
            3 => 'Středa',
            4 => 'Čtvrtek',
            5 => 'Pátek',
            6 => 'Sobota',
        };
    }
}
