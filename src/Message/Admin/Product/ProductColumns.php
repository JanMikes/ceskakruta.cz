<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Product;

use CeskaKruta\Web\FormData\Admin\ProductFormData;
use CeskaKruta\Web\Value\Admin\PackagesSize;

/**
 * Columns of the full product form (create, edit of type 1/2) with the old save rules (PLAN §2.9).
 */
readonly final class ProductColumns
{
    public function __construct(
        public string $name,
        public int $type,
        public string $unit,
        public float $weightPerUnit,
        public null|int $price,
        public null|string $code,
        public bool $canBeSliced,
        public bool $canBePacked,
        public bool $forcePacking,
        public null|int $pricePack,
        public null|string $textAdditional,
        public null|string $text,
        public PackagesSize $packagesSize,
    ) {
    }

    public static function fromFormData(ProductFormData $formData): self
    {
        assert($formData->name !== null && $formData->type !== null && $formData->unit !== null && $formData->weightPerUnit !== null);

        return new self(
            name: $formData->name,
            type: $formData->type,
            unit: $formData->unit,
            weightPerUnit: $formData->weightPerUnit,
            price: $formData->price,
            code: $formData->code,
            canBeSliced: $formData->canBeSliced,
            canBePacked: $formData->canBePacked,
            forcePacking: $formData->canBePacked && $formData->forcePacking,
            // Old form: empty (or 0) pack price → NULL
            pricePack: $formData->pricePack ?: null,
            textAdditional: $formData->textAdditional,
            text: $formData->text,
            packagesSize: PackagesSize::fromInput($formData->packagesSize ?? ''),
        );
    }

    /**
     * @return array<string, null|scalar>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'unit' => $this->unit,
            'weight_per_unit' => $this->weightPerUnit,
            'price' => $this->price,
            'code' => $this->code,
            'can_be_sliced_flag' => $this->canBeSliced,
            'can_be_packed_flag' => $this->canBePacked,
            'force_packing' => $this->forcePacking,
            'price_pack' => $this->pricePack,
            'text_additional' => $this->textAdditional,
            'text' => $this->text,
            'packages_size' => $this->packagesSize->toJson(),
        ];
    }
}
