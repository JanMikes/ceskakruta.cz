<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminSortimentItem
{
    public function __construct(
        public int $id,
        public null|int $categoryId,
        public null|string $name,
        public null|string $text,
        public bool $active,
    ) {
    }
}
