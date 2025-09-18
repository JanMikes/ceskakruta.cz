<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Entity\RecurringOrderSkip;
use CeskaKruta\Web\Message\SkipRecurringOrder;
use CeskaKruta\Web\Repository\RecurringOrderSkipRepository;
use CeskaKruta\Web\Services\OrderingDeadline;
use CeskaKruta\Web\Services\Security\UserProvider;
use CeskaKruta\Web\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class SkipRecurringOrderHandler
{
    public function __construct(
        private RecurringOrderSkipRepository $recurringOrderSkipRepository,
        private OrderingDeadline $orderingDeadline,
        private UserService $userService,
        private UserProvider $userProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SkipRecurringOrder $message): void
    {
        $existingSkip = $this->recurringOrderSkipRepository->findActiveSkip(
            $message->userId,
            $message->dayOfWeek
        );

        if ($existingSkip !== null) {
            return;
        }

        $email = $this->userService->getEmailById($message->userId);
        $user = $this->userProvider->loadUserByIdentifier($email);

        if ($user->preferredPlaceId === null) {
            return;
        }

        $skipUntil = $this->orderingDeadline->nextOrderDay($message->dayOfWeek, $user->preferredPlaceId);

        $skip = new RecurringOrderSkip(
            userId: $message->userId,
            dayOfWeek: $message->dayOfWeek,
            skipUntil: $skipUntil,
        );

        $this->recurringOrderSkipRepository->save($skip);
        $this->entityManager->flush();
    }
}