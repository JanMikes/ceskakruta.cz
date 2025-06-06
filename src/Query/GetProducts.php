<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\Product;
use Doctrine\DBAL\Connection;

final class GetProducts
{
    /** @var array<Product>|null  */
    private null|array $products = null;

    public function __construct(
        readonly private Connection $connection,
        readonly private CartStorage $cartStorage,
        readonly private GetColdProductsCalendar $getColdProductsCalendar,
        readonly private GetPlaces $getPlaces,
    ) {
    }

    /**
     * @return array<int, Product>
     */
    public function all(): array
    {
        if ($this->products === null) {
            $calendar = $this->getColdProductsCalendar->all();
            $week = $this->cartStorage->getWeek();
            $chosenPlaceId = $this->cartStorage->getPickupPlace() ?? $this->cartStorage->getDeliveryPlace();
            $chosenPlace = $chosenPlaceId !== null ? $this->getPlaces->oneById($chosenPlaceId) : null;

            $productRows = $this->connection
                ->executeQuery('SELECT * FROM product WHERE active_flag = 1 AND del_flag = 0 ORDER BY `order` DESC')
                ->fetchAllAssociative();

            /** @var array<int, Product> $products */
            $products = [];

            foreach ($productRows as $productRow) {
                /**
                 * @var array{
                 *     id: int,
                 *     name: string,
                 *     price: null|int,
                 *     text: string|null,
                 *     can_be_packed_flag: int,
                 *     can_be_sliced_flag: int,
                 *     type: null|int,
                 *     half_of_product_id: null|int,
                 *     force_packing: int,
                 *     price_pack: null|int,
                 *     packages_size: string,
                 *     unit: string,
                 *     weight_per_unit: float|numeric-string,
                 * } $productRow
                 */

                if ($productRow['price'] === null) {
                    continue;
                }

                $productId = $productRow['id'];
                $type = $productRow['type'];
                $turkeyType = null;

                if ($type === 3) {
                    $turkeyType = 1;
                }

                if ($type === 4) {
                    $turkeyType = 2;
                }

                $weightFrom = null;
                $weightTo = null;

                if (isset($calendar[$week->year][$week->number][$turkeyType])) {
                    $weightFrom = $calendar[$week->year][$week->number][$turkeyType]->weightFrom;
                    $weightTo = $calendar[$week->year][$week->number][$turkeyType]->weightTo;
                }

                $forcePacking = false;

                if ($productRow['force_packing'] === 1 || ($chosenPlace !== null && $chosenPlace->forcePacking === true)) {
                    $forcePacking = true;
                }

                /** @var array<int> $packagesSize */
                $packagesSize = json_decode($productRow['packages_size']);

                $products[$productId] = new Product(
                    id: $productId,
                    title: $productRow['name'],
                    text: $productRow['text'] ?? '',
                    pricePerUnit: $productRow['price'],
                    canBeSliced: $productRow['can_be_sliced_flag'] === 1,
                    canBePacked: $productRow['can_be_packed_flag'] === 1,
                    packPrice: $productRow['price_pack'],
                    forceSlicing: false,
                    forcePacking: $forcePacking,
                    isHalf: $productRow['half_of_product_id'] !== null,
                    halfOfProductId: $productRow['half_of_product_id'],
                    weightFrom: $weightFrom,
                    weightTo: $weightTo,
                    type: $type,
                    isTurkey: $type === 3 || $type === 4,
                    turkeyType: $turkeyType,
                    unit: $productRow['unit'],
                    weightPerUnit: (float) $productRow['weight_per_unit'],
                    packagesSize: $packagesSize,
                );
            }

            $this->products = $products;
        }

        return $this->products;
    }

    public function oneById(int $productId): Product
    {
        return $this->all()[$productId] ?? throw new \Exception('Product not found');
    }

    /**
     * @return array<int, Product>
     */
    public function getHalves(): array
    {
        $halves = [];

        foreach ($this->all() as $product) {
            if ($product->halfOfProductId !== null) {
                $halves[$product->halfOfProductId] = $product;
            }
        }

        return $halves;
    }
}
