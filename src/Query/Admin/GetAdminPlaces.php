<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminPlace;
use CeskaKruta\Web\Value\Admin\AdminPlaceDay;
use Doctrine\DBAL\Connection;

readonly final class GetAdminPlaces
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Places list: inactive included (they can be activated again), `hide_in_admin` places left out.
     *
     * @return list<AdminPlace>
     */
    public function listed(): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT * FROM place WHERE del_flag = 0 AND hide_in_admin = 0 ORDER BY `order` DESC')
            ->fetchAllAssociative();

        return array_map(self::createPlace(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminPlace
    {
        $row = $this->connection
            ->executeQuery('SELECT * FROM place WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createPlace($row);
    }

    /**
     * Active places for dropdowns (`hide_in_admin` places included, as in the old admin), sorted `order DESC`.
     *
     * @return array<int, string> id => name
     */
    public function namesForChoice(): array
    {
        return $this->choices('name');
    }

    /**
     * @return array<int, string> id => title
     */
    public function titlesForChoice(): array
    {
        return $this->choices('title');
    }

    /**
     * @param 'name'|'title' $labelColumn
     * @return array<int, string>
     */
    private function choices(string $labelColumn): array
    {
        /** @var array<int|string, string> $pairs */
        $pairs = $this->connection
            ->executeQuery(sprintf('SELECT id, `%s` FROM place WHERE active_flag = 1 AND del_flag = 0 ORDER BY `order` DESC', $labelColumn))
            ->fetchAllKeyValue();

        $choices = [];
        foreach ($pairs as $id => $label) {
            $choices[(int) $id] = $label;
        }

        return $choices;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createPlace(array $row): AdminPlace
    {
        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            $daysBefore = $row[sprintf('allow_order_day_%d_days_before', $day)] ?? null;
            $time = $row[sprintf('allow_order_day_%d_time', $day)] ?? null;

            $days[$day] = new AdminPlaceDay(
                day: $day,
                daysBefore: is_numeric($daysBefore) ? (int) $daysBefore : null,
                time: is_string($time) ? substr($time, 0, 5) : null,
            );
        }

        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     title: string,
         *     force_delivery_address: int,
         *     is_cool_balik: null|int,
         *     force_packing: int,
         *     allow_only_wholesale: null|int,
         *     description: null|string,
         *     opening_hours: null|string,
         *     opening_hours_email: null|string,
         *     active_flag: int,
         * } $row
         */

        return new AdminPlace(
            id: $row['id'],
            name: $row['name'],
            title: $row['title'],
            forceDeliveryAddress: (bool) $row['force_delivery_address'],
            isCoolBalik: (bool) $row['is_cool_balik'],
            forcePacking: (bool) $row['force_packing'],
            allowOnlyWholesale: (bool) $row['allow_only_wholesale'],
            description: $row['description'],
            openingHours: $row['opening_hours'],
            openingHoursEmail: $row['opening_hours_email'],
            days: $days,
            active: (bool) $row['active_flag'],
        );
    }
}
