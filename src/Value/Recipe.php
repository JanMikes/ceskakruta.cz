<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value;

readonly final class Recipe
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $name,
        public string $text,
    )
    {

    }
}
