<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class ResetPassword
{
    public function __construct(
        public string $token,
        public string $newPlainTextPassword,
    ) {
    }
}
