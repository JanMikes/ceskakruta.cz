<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Services\Boxxi\ShipmentWeightCalculator;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Old `CoolBalikFormFactory`: all fields required, defaults computed from the order items.
 */
final class BoxxiFormData
{
    #[Assert\NotNull(message: 'Váha je povinná!')]
    #[Assert\PositiveOrZero]
    public null|float $weight = null;

    #[Assert\NotNull(message: 'Cena je povinná!')]
    #[Assert\PositiveOrZero]
    public null|float $coolBalikPrice = null;

    #[Assert\NotNull(message: 'Počet balíků je povinný!')]
    #[Assert\PositiveOrZero]
    public null|int $coolBalikBoxCount = null;

    #[Assert\NotNull(message: 'Datum je povinný!')]
    public null|\DateTimeImmutable $date = null;

    /**
     * @param list<AdminOrderItem> $items
     */
    public static function fromOrder(AdminOrderRecord $record, array $items, ShipmentWeightCalculator $calculator): self
    {
        $formData = new self();
        $formData->weight = $calculator->weight($items);
        $formData->coolBalikPrice = $record->priceTotal;
        $formData->coolBalikBoxCount = $calculator->boxes($formData->weight);
        $formData->date = $record->order->date;

        return $formData;
    }
}
