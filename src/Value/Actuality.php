<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Actuality
{
    public function __construct(
        public int $id,
        public \DateTimeImmutable $date,
        public string $title,
        public string $text,
        public string $textFull,
    ) {
    }
}
