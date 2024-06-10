<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class RequestPasswordResetFormData
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';
}
