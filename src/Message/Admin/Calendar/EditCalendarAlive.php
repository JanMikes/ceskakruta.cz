<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Calendar;

use CeskaKruta\Web\FormData\Admin\CalendarAliveFormData;

readonly final class EditCalendarAlive
{
    public function __construct(
        public int $id,
        public int $adminId,
        public int $productTpId,
        public bool $farmaDoubrava,
        public bool $farmaRychvald,
        public int $weekNumber,
        public int $year,
        public float $weightFrom,
        public float $weightTo,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, CalendarAliveFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            productTpId: (int) $formData->productTpId,
            farmaDoubrava: $formData->farmaDoubrava,
            farmaRychvald: $formData->farmaRychvald,
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
            'farma_doubrava' => $this->farmaDoubrava,
            'farma_rychvald' => $this->farmaRychvald,
            'week_number' => $this->weekNumber,
            'year' => $this->year,
            'weight_from' => $this->weightFrom,
            'weight_to' => $this->weightTo,
        ];
    }
}
