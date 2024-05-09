<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Value\Product;
use Doctrine\DBAL\Connection;

readonly final class GetProducts
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<int, Product>
     */
    public function all(null|int $chosenPlaceId = null): array
    {
        $productRows = $this->connection
            ->executeQuery('SELECT * FROM product WHERE active_flag = 1 AND del_flag = 0')
            ->fetchAllAssociative();

        $productPlaceRows = $this->connection
            ->executeQuery('SELECT * FROM product_place WHERE active_flag = 1 AND del_flag = 0')
            ->fetchAllAssociative();

        // $placePrices[$productId][$placeId] = $price
        /** @var array<int, array<int, int>> $placePrices */
        $placePrices = [];

        /** @var array<int, int> $pricesFrom */
        $pricesFrom = [];

        foreach ($productPlaceRows as $productPlaceRow) {
            /**
             * @var array{
             *     product_id: int,
             *     place_id: int,
             *     price: int,
             * } $productPlaceRow
             */

            $placePrices[$productPlaceRow['product_id']][$productPlaceRow['place_id']] = $productPlaceRow['price'];

            // PriceFrom is set always to the lowest price of the product
            $productPriceFrom = $pricesFrom[$productPlaceRow['product_id']] ?? null;

            if ($productPriceFrom === null || $productPriceFrom > $productPlaceRow['price']) {
                $pricesFrom[$productPlaceRow['product_id']] = $productPlaceRow['price'];
            }
        }

        /** @var array<int, Product> $products */
        $products = [];

        foreach ($productRows as $productRow) {
            /**
             * @var array{
             *     id: int,
             *     name: string,
             *     can_be_packed_flag: int,
             *     can_be_sliced_flag: int,
             * } $productRow
             */

            $priceForChosenPlace = null;

            if ($chosenPlaceId !== null) {
                $priceForChosenPlace = $placePrices[$productRow['id']][$chosenPlaceId];
            }

            $products[$productRow['id']] = new Product(
                id: $productRow['id'],
                title: $productRow['name'],
                priceFrom: $pricesFrom[$productRow['id']],
                priceForChosenPlace: $priceForChosenPlace,
                canBeSliced: $productRow['can_be_sliced_flag'] === 1,
                canBePacked: $productRow['can_be_packed_flag'] === 1,
                forceSlicing: false,
                forcePacking: false,
            );
        }

        return $products;
    }
}
