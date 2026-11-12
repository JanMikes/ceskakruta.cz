<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Order;

use CeskaKruta\Web\FormData\Admin\OrderItemFormData;

readonly final class EditOrderItem
{
    public function __construct(
        /** Existing `order_item.id`, null = new item */
        public null|int $id,
        public int $productId,
        public float $amount,
        public null|float $weightFrom,
        public null|float $weightTo,
        public bool $isSliced,
        public bool $isPacked,
        /** null = current web price */
        public null|int $pricePerUnit,
        public null|string $note,
    ) {
    }

    public static function fromFormData(OrderItemFormData $formData): self
    {
        assert($formData->productId !== null && $formData->amount !== null);
        $note = trim((string) $formData->note);

        return new self(
            id: $formData->id !== null && ctype_digit($formData->id) ? (int) $formData->id : null,
            productId: $formData->productId,
            amount: $formData->amount,
            weightFrom: $formData->weightFrom,
            weightTo: $formData->weightTo,
            isSliced: $formData->isSliced,
            isPacked: $formData->isPacked,
            pricePerUnit: $formData->pricePerUnit,
            note: $note !== '' ? $note : null,
        );
    }
}
