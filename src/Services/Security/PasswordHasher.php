<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

readonly final class PasswordHasher implements PasswordHasherInterface
{
    function __construct(
        private string $leadingSalt,
        private string $trailingSalt,
    ) {
    }


    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return md5($this->leadingSalt . $plainPassword . $this->trailingSalt);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        return $this->hash($plainPassword) === $hashedPassword;
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
