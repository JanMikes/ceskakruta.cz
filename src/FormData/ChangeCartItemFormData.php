<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints\NotBlank;

final class ChangeCartItemFormData
{
    #[NotBlank]
    public int $cartKey = 0;
    public null|float|int $quantity = 0;
    public null|string $note = null;
    public null|bool $slice = null;
    public null|bool $pack = null;
}
