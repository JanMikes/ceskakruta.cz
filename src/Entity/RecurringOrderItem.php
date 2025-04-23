<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use CeskaKruta\Web\Doctrine\PackagesAmountsDoctrineType;
use CeskaKruta\Web\Value\PackageAmount;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;

#[Entity]
class RecurringOrderItem
{
    public function __construct(
        #[Id]
        #[Column(type: UuidType::NAME, unique: true)]
        public UuidInterface $id,

        #[ManyToOne(targetEntity: RecurringOrder::class, inversedBy: 'items')]
        #[JoinColumn(nullable: false, onDelete: "CASCADE")]
        readonly public RecurringOrder $order,

        #[Column]
        readonly public int $productId,

        /** @var array<PackageAmount> */
        #[Column(type: PackagesAmountsDoctrineType::NAME)]
        public array $packages,

        #[Column]
        public float $otherPackageSizeAmount,
    ) {
        $order->addItem($this);
    }

    public function packageSizeAmount(string $packageSize): null|int|float
    {
        if ($packageSize === 'other') {
            return $this->otherPackageSizeAmount;
        }

        foreach ($this->packages as $package) {
            if ($package->sizeG === (float) $packageSize) {
                return $package->amount;
            }
        }

        return null;
    }

    /** @param array<PackageAmount> $packages */
    public function changeQuantities(array $packages, float $otherPackageSizeAmount): void
    {
        $this->packages = $packages;
        $this->otherPackageSizeAmount = $otherPackageSizeAmount;
    }

    public function calculateQuantityInKg(): float
    {
        $quantity = $this->otherPackageSizeAmount;

        foreach ($this->packages as $package) {
            $quantity += $package->sizeG/1000 * $package->amount;
        }

        return $quantity;
    }

    public function quantitiesAsNote(): string
    {
        $note = '';

        foreach ($this->packages as $package) {
            $note .= sprintf(
                '%sBalení %s Kg: %sx',
                strlen($note) > 0 ? ', ' : '',
                $package->sizeG/1000,
                $package->amount,
            );
        }

        if ($this->otherPackageSizeAmount > 0) {
            $note .= sprintf('%sJiné: %s Kg',
                strlen($note) > 0 ? ', ' : '',
                $this->otherPackageSizeAmount
            );
        }

        return $note;
    }
}
