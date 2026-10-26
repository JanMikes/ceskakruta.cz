<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Product;

use CeskaKruta\Web\FormData\Admin\TurkeyProductFormData;

readonly final class EditTurkeyProduct
{
    public function __construct(
        public int $id,
        public int $adminId,
        public null|int $price,
        public null|string $code,
        public null|string $text,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, TurkeyProductFormData $formData): self
    {
        return new self(
            id: $id,
            adminId: $adminId,
            price: $formData->price,
            code: $formData->code,
            text: $formData->text,
        );
    }

    /**
     * @return array<string, null|scalar>
     */
    public function columns(): array
    {
        return [
            'price' => $this->price,
            'code' => $this->code,
            'text' => $this->text,
        ];
    }
}
