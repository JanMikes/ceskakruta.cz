<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'recurring_order_skip')]
#[Index(name: 'idx_user_day', columns: ['user_id', 'day_of_week'])]
class RecurringOrderSkip
{
    public function __construct(
        #[Column]
        readonly public int $userId,

        #[Column]
        readonly public int $dayOfWeek,

        #[Column(type: Types::DATETIME_IMMUTABLE)]
        readonly public DateTimeImmutable $skipUntil,

        #[Id]
        #[GeneratedValue]
        #[Column]
        public null|int $id = null,
    ) {
    }
}