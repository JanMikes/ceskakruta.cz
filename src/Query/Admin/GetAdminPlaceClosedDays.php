<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\AdminPlaceClosedDay;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

readonly final class GetAdminPlaceClosedDays
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Closed days from today − 2 days on, as the old list.
     *
     * @return list<AdminPlaceClosedDay>
     */
    public function recent(): array
    {
        $rows = $this->connection
            ->executeQuery(<<<SQL
SELECT place_closed_day.id, place_closed_day.date, place_closed_day.place_id, place.name AS place_name
FROM place_closed_day
LEFT JOIN place ON place.id = place_closed_day.place_id
WHERE place_closed_day.del_flag = 0 AND place_closed_day.date >= :from
ORDER BY place_closed_day.date ASC, place_closed_day.id ASC
SQL, ['from' => (new DateTimeImmutable('-2 days'))->format('Y-m-d')])
            ->fetchAllAssociative();

        return array_map(static function (array $row): AdminPlaceClosedDay {
            /** @var array{id: int, date: string, place_id: null|int, place_name: null|string} $row */

            return new AdminPlaceClosedDay(
                id: $row['id'],
                date: new DateTimeImmutable($row['date']),
                placeId: $row['place_id'],
                placeName: $row['place_name'],
            );
        }, $rows);
    }
}
