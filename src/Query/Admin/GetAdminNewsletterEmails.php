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

    public function count(): int
    {
        $count = $this->connection->fetchOne('SELECT COUNT(*) FROM newsletter WHERE del_flag = 0');
        assert(is_numeric($count));

        return (int) $count;
    }

    /**
     * Newest first, inactive (unsubscribed) included like in the old list.
     *
     * @return list<AdminNewsletterEmail>
     */
    public function page(int $offset, int $limit): array
    {
        /** @var list<array{id: int, email: null|string, ins_dt: string}> $rows */
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'email', 'ins_dt')
            ->from('newsletter')
            ->where('del_flag = 0')
            ->orderBy('ins_dt', 'DESC')
            ->addOrderBy('id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
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
