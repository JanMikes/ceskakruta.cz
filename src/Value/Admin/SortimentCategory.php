<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class SortimentCategory
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $active,
        /** Non-deleted items in the category, a category with items can not be deleted */
        public int $itemsCount = 0,
    ) {
    }
}
