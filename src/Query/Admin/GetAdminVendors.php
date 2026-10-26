<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminVendor;
use Doctrine\DBAL\Connection;

readonly final class GetAdminVendors
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), sorted like the old list.
     *
     * @return list<AdminVendor>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT * FROM vendor WHERE del_flag = 0 ORDER BY `order` DESC')
            ->fetchAllAssociative();

        return array_map(self::createVendor(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminVendor
    {
        $row = $this->connection
            ->executeQuery('SELECT * FROM vendor WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createVendor($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createVendor(array $row): AdminVendor
    {
        /**
         * @var array{
         *     id: int,
         *     place: null|string,
         *     name: null|string,
         *     address: null|string,
         *     city: null|string,
         *     zip: null|string,
         *     email: null|string,
         *     web: null|string,
         *     phone: null|string,
         *     active_flag: int,
         * } $row
         */

        return new AdminVendor(
            id: $row['id'],
            place: $row['place'],
            name: $row['name'],
            address: $row['address'],
            city: $row['city'],
            zip: $row['zip'],
            email: $row['email'],
            web: $row['web'],
            phone: $row['phone'],
            active: (bool) $row['active_flag'],
        );
    }
}
