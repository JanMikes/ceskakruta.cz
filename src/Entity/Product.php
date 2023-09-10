<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class Product
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public readonly UuidInterface $id,

        #[ORM\Column(type: Types::STRING)]
        public string $name,
    ) {
    }
}
