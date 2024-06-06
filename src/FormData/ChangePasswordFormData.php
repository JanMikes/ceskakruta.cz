<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordFormData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password = '';
}
