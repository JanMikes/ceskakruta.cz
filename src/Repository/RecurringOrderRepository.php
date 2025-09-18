<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Repository;

use CeskaKruta\Web\Entity\RecurringOrder;
use CeskaKruta\Web\Exceptions\RecurringOrderNotFound;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

readonly final class RecurringOrderRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private GetPlaces $getPlaces,
        private UserService $userService,
        private UserProvider $userProvider,
        private LoggerInterface $logger,
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
        $timeForDeadline = $this->clock->now()
            //->modify('-1 day')
            ->setTime(23, 59);

        $users = [];

        /** @var list<RecurringOrder> $orders */
        $orders = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(RecurringOrder::class, 'o')
            ->where('o.lastOrderedAt IS NULL OR o.lastOrderedAt < :timeForDeadline')
            ->setParameter('timeForDeadline', $timeForDeadline)
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

            $place = $this->getPlaces->oneById($user->preferredPlaceId);
            $allowDaysBefore = [
                1 => $place->day1AllowedDaysBefore,
                2 => $place->day2AllowedDaysBefore,
                3 => $place->day3AllowedDaysBefore,
                4 => $place->day4AllowedDaysBefore,
                5 => $place->day5AllowedDaysBefore,
                6 => $place->day6AllowedDaysBefore,
                7 => $place->day7AllowedDaysBefore,
            ];


            $deadlineDaysBefore = $allowDaysBefore[$order->dayOfWeek];

            if (!is_int($deadlineDaysBefore) || $deadlineDaysBefore < 0) {
                $this->logger->error('Invalid deadline for recurring order', [
                    'recurring_order_id' => $order->id,
                ]);

                continue;
            }

            $nextOrderDate = $this->getNextDateByDayOfWeek($order->dayOfWeek, $this->clock->now());
            $deadline = $nextOrderDate->modify("-$deadlineDaysBefore days");

            if ($deadline->format('Y-m-d') === $timeForDeadline->format('Y-m-d')) {
                $scheduledOrders[] = $order;

                if (count($scheduledOrders) >= 5) {
                    break;
                }
            }
        }

        return $scheduledOrders;
    }


    private function getNextDateByDayOfWeek(int $dayOfWeek, DateTimeImmutable $from): DateTimeImmutable
    {
        $currentDow = (int) $from->format('N');
        $daysToAdd = ($dayOfWeek - $currentDow + 7) % 7;

        if ($daysToAdd === 0) {
            $daysToAdd = 7;
        }

        return $from->modify(sprintf('+%d days', $daysToAdd));
    }
}
