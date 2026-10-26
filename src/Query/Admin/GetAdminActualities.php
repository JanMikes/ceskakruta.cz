<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminActuality;
use Doctrine\DBAL\Connection;

readonly final class GetAdminActualities
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), newest first like the old list.
     *
     * @return list<AdminActuality>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT * FROM actuality WHERE del_flag = 0 ORDER BY `date` DESC, id DESC')
            ->fetchAllAssociative();

        return array_map(self::createActuality(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminActuality
    {
        $row = $this->connection
            ->executeQuery('SELECT * FROM actuality WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createActuality($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createActuality(array $row): AdminActuality
    {
        /**
         * @var array{
         *     id: int,
         *     date: null|string,
         *     name: null|string,
         *     text: null|string,
         *     text_full: null|string,
         *     active_flag: int,
         * } $row
         */

        $date = $row['date'] !== null ? \DateTimeImmutable::createFromFormat('!Y-m-d', $row['date']) : false;

        return new AdminActuality(
            id: $row['id'],
            date: $date !== false ? $date : null,
            name: $row['name'],
            text: $row['text'],
            textFull: $row['text_full'],
            active: (bool) $row['active_flag'],
        );
    }
}
