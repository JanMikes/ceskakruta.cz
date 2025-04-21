<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use CeskaKruta\Web\Entity\RecurringOrder;
use CeskaKruta\Web\Exceptions\RecurringOrderNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;

readonly final class RecurringOrderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws RecurringOrderNotFound
     */
    public function get(UuidInterface $orderId): RecurringOrder
    {
        $order = $this->entityManager->find(RecurringOrder::class, $orderId);

        if ($order instanceof RecurringOrder) {
            return $order;
        }

        throw new RecurringOrderNotFound();
    }

    public function save(RecurringOrder $order): void
    {
        $this->entityManager->persist($order);
    }

    public function remove(RecurringOrder $order): void
    {
        $this->entityManager->remove($order);
    }

    /**
     * @return array<int, RecurringOrder>
     */
    public function getForUserByDay(int $userId): array
    {
        $ordersByDay = [];

        /** @var list<RecurringOrder> $orders */
        $orders = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(RecurringOrder::class, 'o')
            ->where('o.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        foreach ($orders as $order) {
            $ordersByDay[$order->dayOfWeek] = $order;
        }

        return $ordersByDay;
    }
}
