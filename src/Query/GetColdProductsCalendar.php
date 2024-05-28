<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\ColdProductCalendar;
use CeskaKruta\Web\Value\ColdProductType;
use Doctrine\DBAL\Connection;

final class GetColdProductsCalendar
{
    /** @var array<int, array<int, array<int, ColdProductCalendar>>>|null  */
    private null|array $weights = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<int, array<int, array<int, ColdProductCalendar>>>
     */
    public function all(): array
    {
        if ($this->weights === null) {
            $now = new \DateTimeImmutable();
            $year = $now->format('Y');
            $week = $now->format('W');

            $placeRows = $this->connection
                ->executeQuery('SELECT * FROM calendar_cold WHERE active_flag = 1 AND del_flag = 0 AND week_number >= :week AND year >= :year ORDER BY year, week_number', [
                    'year' => $year,
                    'week' => $week,
                ])
                ->fetchAllAssociative();

            /** @var array<int, array<int, array<int, ColdProductCalendar>>> $weights */
            $weights = [];

            foreach ($placeRows as $placeRow) {
                /**
                 * @var array{
                 *     product_tp_id: int,
                 *     week_number: int,
                 *     year: int,
                 *     weight_from: string,
                 *     weight_to: string,
                 * } $placeRow
                 */

                $weights[$placeRow['year']][$placeRow['week_number']][$placeRow['product_tp_id']] = new ColdProductCalendar(
                    type: ColdProductType::from($placeRow['product_tp_id']),
                    weightFrom: (float) $placeRow['weight_from'],
                    weightTo: (float) $placeRow['weight_to'],
                );
            }

            $this->weights = $weights;
        }

        return $this->weights;
    }
}
