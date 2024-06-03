<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationFormData
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\Length(max: 255)]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password = '';
}
