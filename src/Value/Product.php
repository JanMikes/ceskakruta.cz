<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public int $priceFrom,
        public null|int $priceForChosenPlace,
        public bool $canBeSliced,
        public bool $canBePacked,
        public bool $forceSlicing,
        public bool $forcePacking,
        public bool $isHalf,
        public null|int $halfOfProductId,
        public null|int $weightFrom,
        public null|int $weightTo,
        private null|int $type,
    ) {
    }

    public function price(): int
    {
        return $this->priceForChosenPlace ?? $this->priceFrom;
    }

    public function isTurkey(): bool
    {
        return $this->type === 3 || $this->type === 4;
    }

    public function getTurkeyType(): null|int
    {
        if ($this->type === 3) {
            return 1;
        }

        if ($this->type === 4) {
            return 2;
        }

        return null;
    }
}
