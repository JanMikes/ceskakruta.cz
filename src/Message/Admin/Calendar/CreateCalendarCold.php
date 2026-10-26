<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Calendar;

use CeskaKruta\Web\FormData\Admin\CalendarColdFormData;

readonly final class CreateCalendarCold
{
    public function __construct(
        public int $adminId,
        public int $productTpId,
        public int $weekNumber,
        public int $year,
        public float $weightFrom,
        public float $weightTo,
    ) {
    }

    public static function fromFormData(int $adminId, CalendarColdFormData $formData): self
    {
        return new self(
            adminId: $adminId,
            productTpId: (int) $formData->productTpId,
            weekNumber: (int) $formData->weekNumber,
            year: (int) $formData->year,
            weightFrom: (float) $formData->weightFrom,
            weightTo: (float) $formData->weightTo,
        );
    }

    /**
     * @return array<string, null|scalar>
     */
    public function columns(): array
    {
        return [
            'product_tp_id' => $this->productTpId,
            'week_number' => $this->weekNumber,
            'year' => $this->year,
            'weight_from' => $this->weightFrom,
            'weight_to' => $this->weightTo,
        ];
    }
}
