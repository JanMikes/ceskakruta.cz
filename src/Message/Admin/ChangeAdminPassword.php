<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin;

readonly final class ChangeAdminPassword
{
    public function __construct(
        public int $userId,
        #[\SensitiveParameter]
        public string $newPlainTextPassword,
    ) {
    }
}
