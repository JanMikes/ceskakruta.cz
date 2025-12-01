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
                ->executeQuery('SELECT * FROM place WHERE active_flag = 1 AND del_flag = 0 ORDER BY `order` DESC')
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
                 *     is_cool_balik: null|int,
                 *     allow_only_wholesale: int,
                 *     allow_order_day_1_days_before: null|int,
                 *     allow_order_day_2_days_before: null|int,
                 *     allow_order_day_3_days_before: null|int,
                 *     allow_order_day_4_days_before: null|int,
                 *     allow_order_day_5_days_before: null|int,
                 *     allow_order_day_6_days_before: null|int,
                 *     allow_order_day_7_days_before: null|int,
                 *     allow_order_day_1_time: null|string,
                 *     allow_order_day_2_time: null|string,
                 *     allow_order_day_3_time: null|string,
                 *     allow_order_day_4_time: null|string,
                 *     allow_order_day_5_time: null|string,
                 *     allow_order_day_6_time: null|string,
                 *     allow_order_day_7_time: null|string,
                 *     address_1: null|string,
                 *     address_2: null|string,
                 *     address_3: null|string,
                 *     map_url: null|string,
                 *     phone: null|string,
                 *     opening_hours: null|string,
                 *     opening_hours_email: null|string,
                 * } $placeRow
                 */

                $places[$placeRow['id']] = new Place(
                    id: $placeRow['id'],
                    name: $placeRow['name'],
                    isDelivery: $placeRow['force_delivery_address'] === 1,
                    isOwnDelivery: $placeRow['is_cool_balik'] !== 1,
                    forcePacking: $placeRow['force_packing'] === 1,
                    allowOnlyWholesale: $placeRow['allow_only_wholesale'] === 1,
                    day1AllowedDaysBefore: $placeRow['allow_order_day_1_days_before'],
                    day2AllowedDaysBefore: $placeRow['allow_order_day_2_days_before'],
                    day3AllowedDaysBefore: $placeRow['allow_order_day_3_days_before'],
                    day4AllowedDaysBefore: $placeRow['allow_order_day_4_days_before'],
                    day5AllowedDaysBefore: $placeRow['allow_order_day_5_days_before'],
                    day6AllowedDaysBefore: $placeRow['allow_order_day_6_days_before'],
                    day7AllowedDaysBefore: $placeRow['allow_order_day_7_days_before'],
                    day1AllowedTime: $this->createDateInterval($placeRow['allow_order_day_1_time']),
                    day2AllowedTime: $this->createDateInterval($placeRow['allow_order_day_2_time']),
                    day3AllowedTime: $this->createDateInterval($placeRow['allow_order_day_3_time']),
                    day4AllowedTime: $this->createDateInterval($placeRow['allow_order_day_4_time']),
                    day5AllowedTime: $this->createDateInterval($placeRow['allow_order_day_5_time']),
                    day6AllowedTime: $this->createDateInterval($placeRow['allow_order_day_6_time']),
                    day7AllowedTime: $this->createDateInterval($placeRow['allow_order_day_7_time']),
                    address1: $placeRow['address_1'],
                    address2: $placeRow['address_2'],
                    address3: $placeRow['address_3'],
                    mapUrl: $placeRow['map_url'],
                    phone: $placeRow['phone'],
                    openingHours: $placeRow['opening_hours'],
                    openingHoursEmail: $placeRow['opening_hours_email'],
                );
            }

            $this->places = $places;
        }

        return $this->places;
    }

    private function createDateInterval(null|string $time): null|\DateInterval
    {
        if ($time === null) {
            return null;
        }

        [$hours, $minutes, $seconds] = explode(':', $time);

        return new \DateInterval(sprintf('PT%dH%dM%dS', (int) $hours, (int) $minutes, (int) $seconds));
    }
}
