<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * One row of the admin order edit form. `id` = existing `order_item`, empty = new item.
 */
final class OrderItemFormData
{
    public null|string $id = null;

    #[Assert\NotNull(message: 'Vyberte produkt.')]
    public null|int $productId = null;

    #[Assert\NotNull(message: 'Zadejte množství.')]
    #[Assert\Positive(message: 'Množství musí být větší než 0.')]
    public null|float $amount = null;

    /** Whole turkeys: weight class (halves: the half's weight), empty = cold calendar of the order week */
    #[Assert\Positive]
    public null|float $weightFrom = null;

    #[Assert\Positive]
    public null|float $weightTo = null;

    public bool $isSliced = false;

    public bool $isPacked = false;

    /** Kč per unit (whole turkeys: per piece) after the coupon discount, empty = current web price */
    #[Assert\PositiveOrZero]
    public null|int $pricePerUnit = null;

    public null|string $note = null;

    public static function fromItem(AdminOrderItem $item): self
    {
        $formData = new self();
        $formData->id = (string) $item->id;
        $formData->productId = $item->productId;
        $formData->amount = $item->amount;
        $formData->weightFrom = $item->weightFrom;
        $formData->weightTo = $item->weightTo;
        $formData->isSliced = $item->isSliced;
        $formData->isPacked = $item->isPacked;
        $formData->pricePerUnit = $item->pricePerUnit;
        $formData->note = $item->note;

        return $formData;
    }

    #[Assert\Callback]
    public function validateWeights(ExecutionContextInterface $context): void
    {
        if (($this->weightFrom === null) !== ($this->weightTo === null)) {
            $context->buildViolation('Zadejte váhu od i do, nebo obě nechte prázdné.')->atPath('weightTo')->addViolation();
        } elseif ($this->weightFrom !== null && $this->weightTo !== null && $this->weightFrom > $this->weightTo) {
            $context->buildViolation('Váha od nesmí být větší než váha do.')->atPath('weightTo')->addViolation();
        }
    }
}
