<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Uuid;

final class AddToCartFormData
{
    public null|bool $slice = null;
    public null|bool $pack = null;
    public null|int $week = null;
    public null|int $year = null;
    #[NotBlank]
    public string $productId = '';
    #[NotBlank]
    public float|int $quantity = 0;
}
