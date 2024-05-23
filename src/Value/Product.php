<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Product
{
    public null|float $weightFrom;
    public null|float $weightTo;

    public function __construct(
        public int $id,
        public string $title,
        public int $priceFrom,
        public null|int $priceForChosenPlace,
        public bool $canBeSliced,
        public bool $canBePacked,
        public null|int $packPrice,
        public bool $forceSlicing,
        public bool $forcePacking,
        public bool $isHalf,
        public null|int $halfOfProductId,
        null|float $weightFrom,
        null|float $weightTo,
        public null|int $type,
        public bool $isTurkey,
        public null|int $turkeyType,
    ) {
        $this->weightFrom = ($isHalf === true && $weightFrom !== null) ? $weightFrom / 2 : $weightFrom;
        $this->weightTo = ($isHalf === true && $weightTo !== null) ? $weightTo / 2 : $weightTo;
    }

    public function pricePerUnit(): int
    {
        return $this->priceForChosenPlace ?? $this->priceFrom;
    }

    public function price(): int|float
    {
        $pricePerUnit = $this->pricePerUnit();

        if ($this->isTurkey) {
            $averageWeight = ($this->weightFrom + $this->weightTo) / 2;

            return $pricePerUnit * $averageWeight;
        }

        return $pricePerUnit;
    }
}
