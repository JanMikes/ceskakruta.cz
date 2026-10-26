<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminCalendarDead;
use Doctrine\DBAL\Connection;

readonly final class GetAdminCalendarDead
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), sorted like the old list.
     *
     * @return list<AdminCalendarDead>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT * FROM calendar_dead WHERE del_flag = 0 ORDER BY year ASC, week_number ASC, id DESC')
            ->fetchAllAssociative();

        return array_map(self::createCalendarDead(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminCalendarDead
    {
        $row = $this->connection
            ->executeQuery('SELECT * FROM calendar_dead WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createCalendarDead($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createCalendarDead(array $row): AdminCalendarDead
    {
        /**
         * @var array{
         *     id: int,
         *     farma_doubrava: null|int,
         *     farma_rychvald: null|int,
         *     week_number: null|int,
         *     year: null|int,
         *     week_age: null|int,
         *     weight: null|string,
         *     price_mix: null|string,
         *     price_man: null|string,
         *     price_woman: null|string,
         *     note: null|string,
         *     active_flag: int,
         * } $row
         */

        return new AdminCalendarDead(
            id: $row['id'],
            farmaDoubrava: (bool) $row['farma_doubrava'],
            farmaRychvald: (bool) $row['farma_rychvald'],
            weekNumber: (int) $row['week_number'],
            year: (int) $row['year'],
            weekAge: $row['week_age'] === null ? null : (int) $row['week_age'],
            weight: $row['weight'],
            priceMix: $row['price_mix'],
            priceMan: $row['price_man'],
            priceWoman: $row['price_woman'],
            note: $row['note'],
            active: (bool) $row['active_flag'],
        );
    }
}
