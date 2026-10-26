<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData\Admin;

use CeskaKruta\Web\Value\Admin\AdminProduct;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Edit of whole turkey products (type 3, 4): only price, code and text (PLAN §2.9, §6 Phase 1 review item 10).
 */
final class TurkeyProductFormData
{
    #[Assert\PositiveOrZero]
    public null|int $price = null;

    #[Assert\Length(max: 100)]
    public null|string $code = null;

    public null|string $text = null;

    public static function fromProduct(AdminProduct $product): self
    {
        $formData = new self();
        $formData->price = $product->price;
        $formData->code = $product->code;
        $formData->text = $product->text;

        return $formData;
    }
}
