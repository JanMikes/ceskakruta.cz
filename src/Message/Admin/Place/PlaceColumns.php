<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Place;

use CeskaKruta\Web\FormData\Admin\PlaceFormData;

/**
 * Editable `place` columns shared by create and edit, a disabled day stores NULL / NULL
 * (explicitly: the `time` column defaults to 23:59:00).
 */
readonly final class PlaceColumns
{
    /**
     * @param array<int<1, 7>, array{days_before: null|int, time: null|string}> $days time HH:MM:SS
     */
    public function __construct(
        public string $name,
        public string $title,
        public bool $allowOnlyWholesale,
        public bool $forceDeliveryAddress,
        public bool $isCoolBalik,
        public bool $forcePacking,
        public array $days,
        public null|string $description,
        public null|string $openingHours,
        public null|string $openingHoursEmail,
    ) {
    }

    public static function fromFormData(PlaceFormData $formData): self
    {
        $days = [];
        foreach ($formData->days as $day => $dayFormData) {
            $days[$day] = [
                'days_before' => $dayFormData->daysBeforeValue(),
                'time' => $dayFormData->timeValue(),
            ];
        }

        return new self(
            name: (string) $formData->name,
            title: (string) $formData->title,
            allowOnlyWholesale: $formData->allowOnlyWholesale,
            forceDeliveryAddress: $formData->forceDeliveryAddress,
            isCoolBalik: $formData->isCoolBalik,
            forcePacking: $formData->forcePacking,
            days: $days,
            description: $formData->description,
            openingHours: $formData->openingHours,
            openingHoursEmail: $formData->openingHoursEmail,
        );
    }

    /**
     * @return array<string, null|scalar>
     */
    public function toArray(): array
    {
        $columns = [
            'name' => $this->name,
            'title' => $this->title,
            'allow_only_wholesale' => $this->allowOnlyWholesale,
            'force_delivery_address' => $this->forceDeliveryAddress,
            'is_cool_balik' => $this->isCoolBalik,
            'force_packing' => $this->forcePacking,
            'description' => $this->description,
            'opening_hours' => $this->openingHours,
            'opening_hours_email' => $this->openingHoursEmail,
        ];

        foreach ($this->days as $day => $values) {
            $columns[sprintf('allow_order_day_%d_days_before', $day)] = $values['days_before'];
            $columns[sprintf('allow_order_day_%d_time', $day)] = $values['time'];
        }

        return $columns;
    }
}
