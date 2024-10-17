<?php

declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class OrderFormData
{
    #[NotBlank]
    public string $name = '';

    #[NotBlank]
    #[Email]
    public string $email = '';

    #[NotBlank]
    #[Regex(
        pattern: '/^\+?\d+(?:\s?\d+)*$/',
        message: 'Telefonní číslo musí mít formát "+420123456789".',
    )]
    public string $phone = '';

    public bool $payByCard = false;
    public null|string $note = null;
    public bool $subscribeToNewsletter = true;
}
