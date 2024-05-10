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
    ) {
    }

    public function price(): int
    {
        return $this->priceForChosenPlace ?? $this->priceFrom;
    }
}
