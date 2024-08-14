<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use Doctrine\DBAL\Connection;

final class GetPlaceClosedDays
{
    /** @var array<string, string>|null  */
    private null|array $closedDays = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function forPlace(int $placeId): array
    {
        if ($this->closedDays === null) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM place_closed_day WHERE date >= :today AND (place_id = :placeId OR place_id IS NULL) AND active_flag = 1 AND del_flag = 0', [
                    'placeId' => $placeId,
                    'today' => (new \DateTimeImmutable())->format('Y-m-d'),
                ])
                ->fetchAllAssociative();

            /** @var array<string, string> $closedDays */
            $closedDays = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     date: string,
                 * } $row
                 */

                $closedDays[$row['date']] = $row['date'];
            }

            $this->closedDays = $closedDays;
        }

        return $this->closedDays;
    }
}
