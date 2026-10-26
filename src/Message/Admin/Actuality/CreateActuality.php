<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Actuality;

use CeskaKruta\Web\FormData\Admin\ActualityFormData;

readonly final class CreateActuality
{
    public function __construct(
        public int $adminId,
        public \DateTimeImmutable $date,
        public string $name,
        public null|string $text,
        public null|string $textFull,
    ) {
    }

    public static function fromFormData(int $adminId, ActualityFormData $formData): self
    {
        assert($formData->date !== null);
        assert($formData->name !== null);

        return new self(
            adminId: $adminId,
            date: $formData->date,
            name: $formData->name,
            text: $formData->text,
            textFull: $formData->textFull,
        );
    }

    /**
     * @return array<string, null|string>
     */
    public function columns(): array
    {
        return [
            'date' => $this->date->format('Y-m-d'),
            'name' => $this->name,
            'text' => $this->text,
            'text_full' => $this->textFull,
        ];
    }
}
