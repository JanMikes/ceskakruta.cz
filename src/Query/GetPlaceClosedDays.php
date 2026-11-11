<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use Doctrine\DBAL\Connection;

/**
 * Date exceptions of the pickup calendar (`place_closed_day`, `place_id` NULL = all places):
 * `is_open = 0` closes a normally open day, `is_open = 1` opens a normally closed day ("Otevřeno navíc").
 */
final class GetPlaceClosedDays
{
    /** @var array<int, array{closed: array<string, string>, open: array<string, int>}> */
    private array $cache = [];

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<string, string> Y-m-d => Y-m-d
     */
    public function forPlace(int $placeId): array
    {
        return $this->load($placeId)['closed'];
    }

    /**
     * Extra opening days.
     *
     * @return array<string, int> Y-m-d => days before the date the order must be placed
     */
    public function openForPlace(int $placeId): array
    {
        return $this->load($placeId)['open'];
    }

    /**
     * @return array{closed: array<string, string>, open: array<string, int>}
     */
    private function load(int $placeId): array
    {
        if (!isset($this->cache[$placeId])) {
            $rows = $this->connection
                ->executeQuery('SELECT date, is_open, days_before FROM place_closed_day WHERE date >= :today AND (place_id = :placeId OR place_id IS NULL) AND active_flag = 1 AND del_flag = 0', [
                    'placeId' => $placeId,
                    'today' => (new \DateTimeImmutable())->format('Y-m-d'),
                ])
                ->fetchAllAssociative();

            $closed = [];
            $open = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     date: string,
                 *     is_open: int|string,
                 *     days_before: null|int|string,
                 * } $row
                 */

                if ((int) $row['is_open'] === 1) {
                    // the most restrictive deadline wins when there is a place row and an "all places" row
                    $open[$row['date']] = max($open[$row['date']] ?? 0, (int) $row['days_before']);
                } else {
                    $closed[$row['date']] = $row['date'];
                }
            }

            $this->cache[$placeId] = ['closed' => $closed, 'open' => $open];
        }

        return $this->cache[$placeId];
    }
}
