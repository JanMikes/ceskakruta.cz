<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminContent
{
    public function __construct(
        public int $id,
        public null|string $code,
        public null|string $name,
        public null|string $text,
    ) {
    }
}
