<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints\NotBlank;

final class ChangeCartItemQuantityFormData
{
    #[NotBlank]
    public int $cartKey = 0;
    #[NotBlank]
    public float|int $quantity = 0;
}
