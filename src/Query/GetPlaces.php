<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Place;
use Doctrine\DBAL\Connection;

readonly final class GetPlaces
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<int, Place>
     */
    public function all(): array
    {
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
             * } $placeRow
             */

            $places[$placeRow['id']] = new Place(
                id: $placeRow['id'],
                name: $placeRow['name'],
                isDelivery: $placeRow['force_delivery_address'] === 1,
            );
        }

        return $places;
    }
}
