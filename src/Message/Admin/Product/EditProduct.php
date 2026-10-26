<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Product;

use CeskaKruta\Web\FormData\Admin\ProductFormData;

readonly final class EditProduct
{
    public function __construct(
        public int $id,
        public int $adminId,
        public ProductColumns $columns,
    ) {
    }

    public static function fromFormData(int $id, int $adminId, ProductFormData $formData): self
    {
        return new self($id, $adminId, ProductColumns::fromFormData($formData));
    }
}
