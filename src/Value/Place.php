<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Place
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isDelivery,
        public bool $forcePacking,
    ) {
    }
}
