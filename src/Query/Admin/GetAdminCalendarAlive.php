<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminCalendarAlive;
use Doctrine\DBAL\Connection;

readonly final class GetAdminCalendarAlive
{
    private const SELECT = 'SELECT c.*, tp.name AS product_name FROM calendar_alive c LEFT JOIN lst_product_tp tp ON tp.id = c.product_tp_id';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), sorted like the old list.
     *
     * @return list<AdminCalendarAlive>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery(self::SELECT . ' WHERE c.del_flag = 0 ORDER BY c.year ASC, c.week_number ASC, c.product_tp_id DESC, c.id DESC')
            ->fetchAllAssociative();

        return array_map(self::createCalendarAlive(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminCalendarAlive
    {
        $row = $this->connection
            ->executeQuery(self::SELECT . ' WHERE c.id = :id AND c.del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createCalendarAlive($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createCalendarAlive(array $row): AdminCalendarAlive
    {
        /**
         * @var array{
         *     id: int,
         *     product_tp_id: null|int,
         *     product_name: null|string,
         *     farma_doubrava: null|int,
         *     farma_rychvald: null|int,
         *     week_number: null|int,
         *     year: null|int,
         *     weight_from: null|float|string,
         *     weight_to: null|float|string,
         *     active_flag: int,
         * } $row
         */

        return new AdminCalendarAlive(
            id: $row['id'],
            productTpId: $row['product_tp_id'] === null ? null : (int) $row['product_tp_id'],
            productName: $row['product_name'],
            farmaDoubrava: (bool) $row['farma_doubrava'],
            farmaRychvald: (bool) $row['farma_rychvald'],
            weekNumber: (int) $row['week_number'],
            year: (int) $row['year'],
            weightFrom: $row['weight_from'] === null ? null : round((float) $row['weight_from'], 3),
            weightTo: $row['weight_to'] === null ? null : round((float) $row['weight_to'], 3),
            active: (bool) $row['active_flag'],
        );
    }
}
