<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Product;

use CeskaKruta\Web\FormData\Admin\ProductFormData;

readonly final class CreateProduct
{
    public function __construct(
        public int $adminId,
        public ProductColumns $columns,
    ) {
    }

    public static function fromFormData(int $adminId, ProductFormData $formData): self
    {
        return new self($adminId, ProductColumns::fromFormData($formData));
    }
}
