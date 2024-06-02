<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Message\RegisterUser;
use CeskaKruta\Web\Services\Security\PasswordHasher;
use CeskaKruta\Web\Value\User;
use Doctrine\DBAL\Connection;

final class UserService
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private Connection $connection,
    ) {
    }

    public function register(RegisterUser $message): void
    {
        $password = $this->passwordHasher->hash($message->plainTextPassword);
        $now = new \DateTimeImmutable();

        $this->connection->insert('`user`', [
            'name' => $message->name,
            'email' => $message->email,
            'phone' => $message->phone,
            'password' => $password,
            'is_admin' => 0,
            'active_flag' => 1,
            'ins_dt' => $now->format('Y-m-d H:i:s'),
        ]);
    }
}
