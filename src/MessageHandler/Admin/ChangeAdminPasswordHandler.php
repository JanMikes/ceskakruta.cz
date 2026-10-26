<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin;

use CeskaKruta\Web\Message\Admin\ChangeAdminPassword;
use CeskaKruta\Web\Services\Security\PasswordHasher;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Same salted MD5 as the old app — the `user` table is shared with it (docs/admin/PLAN.md §0).
 */
#[AsMessageHandler]
readonly final class ChangeAdminPasswordHandler
{
    public function __construct(
        private Connection $connection,
        private PasswordHasher $passwordHasher,
    ) {
    }

    public function __invoke(ChangeAdminPassword $message): void
    {
        $this->connection->update(
            '`user`',
            [
                'password' => $this->passwordHasher->hash($message->newPlainTextPassword),
                'upd_process' => 'admin',
                'upd_user_id' => (string) $message->userId,
            ],
            [
                'id' => $message->userId,
            ],
        );
    }
}
