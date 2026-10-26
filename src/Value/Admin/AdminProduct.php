<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminProduct
{
    /**
     * Whole turkey products — fixed part of the offer, the admin edits only price, code and text (PLAN §2.9).
     */
    private const TURKEY_TYPES = [3, 4];

    public function __construct(
        public int $id,
        public null|string $name,
        public null|int $type,
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
        public bool $active,
        public int $recipesCount,
    ) {
    }

    public function isTurkey(): bool
    {
        return in_array($this->type, self::TURKEY_TYPES, true);
    }
}
