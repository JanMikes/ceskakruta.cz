<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class ChangePassword
{
    public function __construct(
        public string $email,
        public string $newPlainTextPassword
    ) {
    }
}
