<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Place;
use Doctrine\DBAL\Connection;

final class GetPlaces
{
    /** @var array<Place>|null  */
    private null|array $places = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    public function oneById(int $placeId): Place
    {
        return $this->all()[$placeId];
    }

    /**
     * @return array<int, Place>
     */
    public function all(): array
    {
        if ($this->places === null) {
            $placeRows = $this->connection
                ->executeQuery('SELECT * FROM place WHERE active_flag = 1 AND del_flag = 0')
                ->fetchAllAssociative();

            /** @var array<int, Place> $places */
            $places = [];

            foreach ($placeRows as $placeRow) {
                /**
                 * @var array{
                 *     id: int,
                 *     name: string,
                 *     force_delivery_address: int,
                 *     force_packing: int,
                 *     allow_order_day_1_days_before: null|int,
                 *     allow_order_day_2_days_before: null|int,
                 *     allow_order_day_3_days_before: null|int,
                 *     allow_order_day_4_days_before: null|int,
                 *     allow_order_day_5_days_before: null|int,
                 *     allow_order_day_6_days_before: null|int,
                 *     allow_order_day_7_days_before: null|int,
                 *     address_1: null|string,
                 *     address_2: null|string,
                 *     address_3: null|string,
                 *     map_url: null|string,
                 *     phone: null|string,
                 *     opening_hours: null|string,
                 * } $placeRow
                 */

                $places[$placeRow['id']] = new Place(
                    id: $placeRow['id'],
                    name: $placeRow['name'],
                    isDelivery: $placeRow['force_delivery_address'] === 1,
                    forcePacking: $placeRow['force_packing'] === 1,
                    day1AllowedDaysBefore: $placeRow['allow_order_day_1_days_before'],
                    day2AllowedDaysBefore: $placeRow['allow_order_day_2_days_before'],
                    day3AllowedDaysBefore: $placeRow['allow_order_day_3_days_before'],
                    day4AllowedDaysBefore: $placeRow['allow_order_day_4_days_before'],
                    day5AllowedDaysBefore: $placeRow['allow_order_day_5_days_before'],
                    day6AllowedDaysBefore: $placeRow['allow_order_day_6_days_before'],
                    day7AllowedDaysBefore: $placeRow['allow_order_day_7_days_before'],
                    address1: $placeRow['address_1'],
                    address2: $placeRow['address_2'],
                    address3: $placeRow['address_3'],
                    mapUrl: $placeRow['map_url'],
                    phone: $placeRow['phone'],
                    openingHours: $placeRow['opening_hours'],
                );
            }

            $this->places = $places;
        }

        return $this->places;
    }
}
