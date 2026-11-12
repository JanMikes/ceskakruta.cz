<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Order;

use CeskaKruta\Web\FormData\Admin\OrderEditFormData;
use CeskaKruta\Web\FormData\Admin\OrderItemFormData;

/**
 * Admin edits an order: header + items, prices recalculated by the web rules. The handler returns an `OrderEditResult`.
 */
readonly final class EditOrder
{
    /**
     * @param array<int, EditOrderItem> $items keyed by the form row index
     */
    public function __construct(
        public int $orderId,
        public int $adminId,
        public int $placeId,
        public \DateTimeImmutable $date,
        public string $name,
        public string $phone,
        public string $email,
        public null|string $deliveryStreet,
        public null|string $deliveryCity,
        public null|string $deliveryPostalCode,
        public bool $payByCard,
        public string $note,
        public array $items,
        public bool $notifyCustomer,
    ) {
    }

    public static function fromFormData(int $orderId, int $adminId, OrderEditFormData $formData): self
    {
        assert($formData->placeId !== null && $formData->date !== null);

        $trim = static fn (null|string $value): null|string => $value !== null && trim($value) !== '' ? trim($value) : null;

        return new self(
            orderId: $orderId,
            adminId: $adminId,
            placeId: $formData->placeId,
            date: $formData->date,
            name: trim($formData->name),
            phone: trim($formData->phone),
            email: trim($formData->email),
            deliveryStreet: $trim($formData->deliveryStreet),
            deliveryCity: $trim($formData->deliveryCity),
            deliveryPostalCode: $trim($formData->deliveryPostalCode),
            payByCard: $formData->payByCard,
            note: trim((string) $formData->note),
            items: array_map(static fn (OrderItemFormData $item): EditOrderItem => EditOrderItem::fromFormData($item), $formData->items),
            notifyCustomer: $formData->notifyCustomer,
        );
    }
}
