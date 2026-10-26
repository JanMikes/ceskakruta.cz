<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminActuality
{
    public function __construct(
        public int $id,
        public null|\DateTimeImmutable $date,
        public null|string $name,
        public null|string $text,
        public null|string $textFull,
        public bool $active,
    ) {
    }
}
