<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\AliveCalendar;
use Doctrine\DBAL\Connection;

final class GetAliveCalendar
{
    /** @var array<int, array<int, array<AliveCalendar>>>|null  */
    private null|array $calendar = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<int, array<int, array<AliveCalendar>>>
     */
    public function all(): array
    {
        if ($this->calendar === null) {
            $now = new \DateTimeImmutable();
            $year = $now->format('Y');
            $week = $now->format('W');

            $rows = $this->connection
                ->executeQuery('SELECT * FROM calendar_dead WHERE active_flag = 1 AND del_flag = 0 AND ((week_number >= :week AND year = :year) OR year > :year) ORDER BY year, week_number', [
                    'year' => $year,
                    'week' => $week,
                ])
                ->fetchAllAssociative();

            /** @var array<int, array<int, array<AliveCalendar>>> $calendar */
            $calendar = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     week_number: int,
                 *     year: int,
                 *     farma_doubrava: null|int,
                 *     farma_rychvald: null|int,
                 *     weight: null|string,
                 *     week_age: null|int,
                 *     price_mix: null|string,
                 *     price_man: null|string,
                 *     price_woman: null|string,
                 *     note: null|string,
                 * } $row
                 */

                $calendar[$row['year']][$row['week_number']][] = new AliveCalendar(
                    weeks: $row['week_age'],
                    weight: $row['weight'],
                    doubravaAvailable: $row['farma_doubrava'] === 1,
                    rychvaldAvailable: $row['farma_rychvald'] === 1,
                    priceMix: $row['price_mix'],
                    priceMan: $row['price_man'],
                    priceWoman: $row['price_woman'],
                    note: $row['note'],
                );
            }

            $this->calendar = $calendar;
        }

        return $this->calendar;
    }
}
