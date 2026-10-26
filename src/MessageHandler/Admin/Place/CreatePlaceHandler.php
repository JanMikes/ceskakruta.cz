<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Place;

use CeskaKruta\Web\Message\Admin\Place\CreatePlace;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreatePlaceHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
        private Connection $connection,
    ) {
    }

    /**
     * @return int id of the new place
     */
    public function __invoke(CreatePlace $message): int
    {
        $id = $this->repository->insert('place', $message->columns->toArray(), $message->adminId, orderable: true);

        if ($message->pricesFromPlaceId !== null) {
            /** @var array<int|string, int|string> $prices */
            $prices = $this->connection
                ->executeQuery(
                    'SELECT product_id, price FROM product_place WHERE place_id = :placeId AND active_flag = 1 AND del_flag = 0',
                    ['placeId' => $message->pricesFromPlaceId],
                )
                ->fetchAllKeyValue();

            foreach ($prices as $productId => $price) {
                $this->repository->insert('product_place', [
                    'place_id' => $id,
                    'product_id' => (int) $productId,
                    'price' => (int) $price,
                ], $message->adminId);
            }
        }

        return $id;
    }
}
