<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Calendar;

use CeskaKruta\Web\FormData\Admin\CalendarDeadFormData;

readonly final class CreateCalendarDead
{
    public function __construct(
        public int $adminId,
        public bool $farmaDoubrava,
        public bool $farmaRychvald,
        public int $weekNumber,
        public int $year,
        public null|int $weekAge,
        public null|string $weight,
        public null|string $priceMix,
        public null|string $priceMan,
        public null|string $priceWoman,
        public null|string $note,
    ) {
    }

    public static function fromFormData(int $adminId, CalendarDeadFormData $formData): self
    {
        return new self(
            adminId: $adminId,
            farmaDoubrava: $formData->farmaDoubrava,
            farmaRychvald: $formData->farmaRychvald,
            weekNumber: (int) $formData->weekNumber,
            year: (int) $formData->year,
            weekAge: $formData->weekAge === null ? null : (int) $formData->weekAge,
            weight: $formData->weight,
            priceMix: $formData->priceMix,
            priceMan: $formData->priceMan,
            priceWoman: $formData->priceWoman,
            note: $formData->note,
        );
    }

    /**
     * @return array<string, null|scalar>
     */
    public function columns(): array
    {
        return [
            'farma_doubrava' => $this->farmaDoubrava,
            'farma_rychvald' => $this->farmaRychvald,
            'week_number' => $this->weekNumber,
            'year' => $this->year,
            'week_age' => $this->weekAge,
            'weight' => $this->weight,
            'price_mix' => $this->priceMix,
            'price_man' => $this->priceMan,
            'price_woman' => $this->priceWoman,
            'note' => $this->note,
        ];
    }
}
