<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Services\Calendar;
use CeskaKruta\Web\Value\ColdProductCalendar;
use CeskaKruta\Web\Value\ColdProductType;
use Doctrine\DBAL\Connection;

final class GetColdProductsCalendar
{
    /** @var array<int, array<int, array<int, ColdProductCalendar>>>|null  */
    private null|array $weights = null;

    public function __construct(
        readonly private Connection $connection,
        readonly private Calendar $calendar,
    ) {
    }

    /**
     * @return array<int, array<int, array<int, ColdProductCalendar>>>
     */
    public function all(): array
    {
        if ($this->weights === null) {
            $currentWeek = $this->calendar->getCurrentWeek();

            $rows = $this->connection
                ->executeQuery('SELECT * FROM calendar_cold WHERE active_flag = 1 AND del_flag = 0 AND week_number >= :week AND year >= :year ORDER BY year, week_number', [
                    'year' => $currentWeek->year,
                    'week' => $currentWeek->number,
                ])
                ->fetchAllAssociative();

            /** @var array<int, array<int, array<int, ColdProductCalendar>>> $weights */
            $weights = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     product_tp_id: int,
                 *     week_number: int,
                 *     year: int,
                 *     weight_from: string,
                 *     weight_to: string,
                 * } $row
                 */

                $weights[$row['year']][$row['week_number']][$row['product_tp_id']] = new ColdProductCalendar(
                    type: ColdProductType::from($row['product_tp_id']),
                    weightFrom: (float) $row['weight_from'],
                    weightTo: (float) $row['weight_to'],
                );
            }

            $this->weights = $weights;
        }

        return $this->weights;
    }
}
