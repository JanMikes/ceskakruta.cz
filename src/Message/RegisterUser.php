<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class RegisterUser
{
    public function __construct(
        public string $email,
        public string $plainTextPassword,
        public null|string $name,
        public null|string $phone,
    ) {
    }
}
