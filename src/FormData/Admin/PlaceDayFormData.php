<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminPlaceDay;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * One weekday of the place form: `enable_day_N`, `allow_order_day_N_days_before`, `allow_order_day_N_time`.
 */
final class PlaceDayFormData
{
    public const DEFAULT_TIME = '23:59';

    public bool $enabled = false;

    #[Assert\Regex(pattern: '/^\d+$/', message: 'Dny musí být celé číslo!')]
    #[Assert\Range(max: 255)]
    public null|string $daysBefore = null;

    // Old pattern, a single-digit hour is accepted too
    #[Assert\Regex(pattern: '/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/', message: 'Čas musí být ve formátu HH:MM (00:00 - 23:59)')]
    public null|string $time = self::DEFAULT_TIME;

    public static function fromDay(AdminPlaceDay $day): self
    {
        $formData = new self();
        $formData->enabled = $day->isEnabled();
        $formData->daysBefore = $day->daysBefore === null ? null : (string) $day->daysBefore;
        $formData->time = $day->time ?? self::DEFAULT_TIME;

        return $formData;
    }

    #[Assert\Callback]
    public function validateDaysBeforeFilled(ExecutionContextInterface $context): void
    {
        if ($this->enabled && $this->daysBefore === null) {
            $context->buildViolation('Vyplňte, kolik dnů předem je potřeba objednat.')
                ->atPath('daysBefore')
                ->addViolation();
        }
    }

    public function daysBeforeValue(): null|int
    {
        return $this->enabled && $this->daysBefore !== null ? (int) $this->daysBefore : null;
    }

    /**
     * HH:MM:SS for the `time` column, empty time of an enabled day = 23:59.
     */
    public function timeValue(): null|string
    {
        if (!$this->enabled) {
            return null;
        }

        [$hours, $minutes] = explode(':', $this->time ?? self::DEFAULT_TIME) + [1 => '00'];

        return sprintf('%02d:%02d:00', (int) $hours, (int) $minutes);
    }
}
