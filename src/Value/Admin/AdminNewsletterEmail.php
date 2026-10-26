<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminNewsletterEmail
{
    public function __construct(
        public int $id,
        public null|string $email,
        public \DateTimeImmutable $insertedAt,
    ) {
    }
}
