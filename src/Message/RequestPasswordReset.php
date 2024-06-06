<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class RequestPasswordReset
{
    public function __construct(
        public string $userId,
        public string $email,
    ) {
    }
}
