<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use CeskaKruta\Web\Entity\RecurringOrder;
use CeskaKruta\Web\Exceptions\RecurringOrderNotFound;
use CeskaKruta\Web\Services\OrderingDeadline;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\UuidInterface;

readonly final class RecurringOrderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private OrderingDeadline $orderingDeadline,
        private UserService $userService,
        private UserProvider $userProvider,
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

    /**
     * @return list<RecurringOrder>
     */
    public function getScheduledForOrdering(): array
    {
        $scheduledOrders = [];
        $now = $this->clock->now();

        $users = [];

        /** @var list<RecurringOrder> $orders */
        $orders = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(RecurringOrder::class, 'o')
            ->orderBy('o.lastOrderedAt', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($orders as $order) {
            if (isset($users[$order->userId]) === false) {
                $email = $this->userService->getEmailById($order->userId);
                $users[$order->userId] = $this->userProvider->loadUserByIdentifier($email);
            }

            $user = $users[$order->userId];
            assert($user->preferredPlaceId !== null);

            $deadline = $this->orderingDeadline->deadlineForCurrentPeriod($order->dayOfWeek, $user->preferredPlaceId);

            // Order is due if: deadline has passed (within 24h window) AND order wasn't processed after this deadline
            $deadlinePlus24h = $deadline->modify('+24 hours');
            if ($now > $deadline && $now < $deadlinePlus24h && ($order->lastOrderedAt === null || $order->lastOrderedAt < $deadline)) {
                $scheduledOrders[] = $order;

                if (count($scheduledOrders) >= 5) {
                    break;
                }
            }
        }

        return $scheduledOrders;
    }
}
