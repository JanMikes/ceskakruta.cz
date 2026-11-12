<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Admin order editing: header, delivery and items. Delivery address requirement depends on the chosen place and is
 * checked by the controller (it knows the places).
 */
final class OrderEditFormData
{
    #[Assert\NotNull(message: 'Vyberte místo.')]
    public null|int $placeId = null;

    #[Assert\NotNull(message: 'Zadejte datum.')]
    public null|\DateTimeImmutable $date = null;

    #[Assert\NotBlank(message: 'Zadejte jméno.')]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\NotBlank(message: 'Zadejte telefon.')]
    #[Assert\Length(max: 255)]
    public string $phone = '';

    #[Assert\NotBlank(message: 'Zadejte e-mail.')]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    public string $email = '';

    #[Assert\Length(max: 255)]
    public null|string $deliveryStreet = null;

    #[Assert\Length(max: 255)]
    public null|string $deliveryCity = null;

    #[Assert\Length(max: 255)]
    public null|string $deliveryPostalCode = null;

    public bool $payByCard = false;

    public null|string $note = null;

    /** @var array<int, OrderItemFormData> keys = form collection indexes (sparse after removing rows) */
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'Objednávka musí mít alespoň jednu položku.')]
    public array $items = [];

    /** Email the customer the changes (before → after) */
    public bool $notifyCustomer = false;

    /**
     * @param list<AdminOrderDetailItem> $items
     */
    public static function fromRecord(AdminOrderRecord $record, array $items): self
    {
        $order = $record->order;
        $formData = new self();
        $formData->placeId = $order->place->id !== 0 ? $order->place->id : null;
        $formData->date = $order->date;
        $formData->name = $order->name;
        $formData->phone = $order->phone;
        $formData->email = $order->email;
        $formData->deliveryStreet = $order->deliveryStreet;
        $formData->deliveryCity = $order->deliveryCity;
        $formData->deliveryPostalCode = $order->deliveryPostalCode;
        $formData->payByCard = $order->payByCard;
        $formData->note = $order->note;
        $formData->items = array_map(static fn (AdminOrderDetailItem $item): OrderItemFormData => OrderItemFormData::fromItem($item->item), $items);

        return $formData;
    }
}
