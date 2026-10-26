<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

readonly final class PendingNewsletter
{
    public function __construct(
        public int $id,
        public int $actualityId,
        public string $email,
        public string $name,
        public string $text,
    ) {
    }
}
