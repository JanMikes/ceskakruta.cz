<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use CeskaKruta\Web\Doctrine\PriceDoctrineType;
use CeskaKruta\Web\Value\Price;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class ProductVariant
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public readonly UuidInterface $id,

        #[ORM\ManyToOne(targetEntity: Product::class)]
        #[ORM\JoinColumn(nullable: false)]
        public readonly Product $product,

        #[ORM\Column(type: Types::STRING)]
        public string $name,

        #[ORM\Column(type: PriceDoctrineType::NAME)]
        public Price $price,
    ) {
    }
}
