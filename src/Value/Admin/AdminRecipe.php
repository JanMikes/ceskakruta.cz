<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class AdminRecipe
{
    public function __construct(
        public int $id,
        public int $productId,
        public null|string $name,
        public null|string $text,
        public null|int $photoId,
        /** Public path of the photo, relative to public/ (`photo.path/photo.filename`, e.g. `upload/recipes/x.jpg`) */
        public null|string $photo,
        public bool $active,
    ) {
    }
}
