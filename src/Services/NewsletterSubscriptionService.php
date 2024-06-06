<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Message\RegisterUser;
use CeskaKruta\Web\Services\Security\PasswordHasher;
use Doctrine\DBAL\Connection;

readonly final class NewsletterSubscriptionService
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function isSubscribed(string $email): bool
    {
        $rows = $this->connection
            ->executeQuery(
                'SELECT * FROM newsletter WHERE active_flag = 1 AND del_flag = 0 AND email = :email',
                ['email' => $email],
            )
            ->fetchAllAssociative();

        return count($rows) > 0;
    }

    public function subscribe(string $email): void
    {
        $now = new \DateTimeImmutable();

        $this->connection->insert('newsletter', [
            'email' => $email,
            'ins_dt' => $now->format('Y-m-d H:i:s'),
        ]);
    }
}
