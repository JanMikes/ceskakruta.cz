<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid;

final class AddToCartFormData
{
    #[NotBlank]
    public string $productId = '';
    #[NotBlank]
    public float|int $quantity = 0;
}
