<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class NewsletterSubscription
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: Types::STRING, length: 150)]
        public readonly string $email,

        #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
        public readonly \DateTimeImmutable $subscribedAt,
    ) {
    }
}
