<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

use Symfony\Component\Asset\Package;

readonly final class Product
{
    public null|float $weightFrom;
    public null|float $weightTo;

    public function __construct(
        public int $id,
        public string $title,
        public string $text,
        public int $pricePerUnit,
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
        public string $unit,
        public float $weightPerUnit,
        /** @var array<int> */
        private array $packagesSize,
    ) {
        $this->weightFrom = ($isHalf === true && $weightFrom !== null) ? $weightFrom / 2 : $weightFrom;
        $this->weightTo = ($isHalf === true && $weightTo !== null) ? $weightTo / 2 : $weightTo;
    }

    public function price(null|Coupon $coupon = null): int
    {
        $pricePerUnit = $this->pricePerUnit;

        if ($coupon?->percentValue !== null) {
            $pricePerUnit = (int) round($pricePerUnit * (100 - $coupon->percentValue)/100);
        }

        if ($this->isTurkey) {
            $averageWeight = ($this->weightFrom + $this->weightTo) / 2;

            return (int) round($pricePerUnit * $averageWeight);
        }

        return $pricePerUnit;
    }

    /**
     * @return array<int, string>
     */
    public function packagesSize(): array
    {
        $packages = [];

        foreach ($this->packagesSize as $packageSize) {
            $packages[$packageSize] = number_format($packageSize/1000, 1, '.') . ' Kg';
        }

        return $packages;
    }
}
