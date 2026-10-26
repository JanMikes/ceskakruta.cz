<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\AdminNewsletterEmail;
use Doctrine\DBAL\Connection;

readonly final class GetAdminNewsletterEmails
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive (unsubscribed) included like in the old list.
     *
     * @return list<AdminNewsletterEmail>
     */
    public function all(): array
    {
        /** @var list<array{id: int, email: null|string, ins_dt: string}> $rows */
        $rows = $this->connection
            ->executeQuery('SELECT id, email, ins_dt FROM newsletter WHERE del_flag = 0 ORDER BY email ASC')
            ->fetchAllAssociative();

        return array_map(
            static fn (array $row): AdminNewsletterEmail => new AdminNewsletterEmail(
                id: $row['id'],
                email: $row['email'],
                insertedAt: new \DateTimeImmutable($row['ins_dt']),
            ),
            $rows,
        );
    }
}
