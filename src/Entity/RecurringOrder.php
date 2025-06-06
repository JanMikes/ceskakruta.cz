<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;

#[Entity]
class RecurringOrder
{
    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public null|DateTimeImmutable $lastOrderedAt = null;

    /** @var Collection<int, RecurringOrderItem>  */
    #[OneToMany(targetEntity: RecurringOrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    public Collection $items;

    public function __construct(
        #[Id]
        #[Column(type: UuidType::NAME, unique: true)]
        public UuidInterface $id,

        #[Column]
        readonly public int $userId,

        #[Column]
        readonly public int $dayOfWeek,
    ) {
        $this->items = new ArrayCollection();
    }

    public function itemForProduct(int $productId): null|RecurringOrderItem
    {
        foreach ($this->items as $item) {
            if ($item->productId === $productId) {
                return $item;
            }
        }

        return null;
    }

    public function addItem(RecurringOrderItem $item): void
    {
        $this->items->add($item);
    }

    public function removeItem(RecurringOrderItem $item): void
    {
        $this->items->removeElement($item);
    }

    public function updateLastOrdered(DateTimeImmutable $now): void
    {
        $this->lastOrderedAt = $now;
    }
}
