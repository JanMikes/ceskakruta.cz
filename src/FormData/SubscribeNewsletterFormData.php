<?php
declare(strict_types=1);

namespace CeskaKruta\Web\FormData;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class SubscribeNewsletterFormData
{
    #[NotBlank]
    #[Email]
    public string $email = '';
}
