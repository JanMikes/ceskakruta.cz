<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class OrderItem
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        public readonly UuidInterface $id,

        #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
        #[ORM\JoinColumn(nullable: false)]
        public readonly Order $order,
    ) {
    }
}
